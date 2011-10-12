<?php

class Shop_PaymentController extends Zend_Controller_Action 
{
    protected $_user;
    protected $_userFinanceInfo;
    protected $_testMode;
    
	function preDispatch()
	{
		Zend_Session::start();
		
		$this->_helper->layout->setLayout('layout-store-payment');
		
		$this->_testMode=false;
		//$this->_testMode=true;
	}
	function processAction()
	{
		$this->_helper->viewRenderer->setNoRender(TRUE);
		$this->_checkAuth();
		
		$orderId = $this->_request->getParam('orderId');
		
		if(empty($orderId)) $this->_redirect(ROOT_URL.'/shop/payment/cartempty');
		
		$modelAppStore = new App_Model_Store();
		if(!$modelAppStore->isUserOwnOrder($this->_user->kopel, $orderId))
		{
			$this->_helper->redirector->gotoSimple('error', 'manager', 'shop',array('view'=>'notowner'));
			die();
		}
		if($modelAppStore->isOrderPaid($orderId))
		{
			$this->_helper->redirector->gotoSimple('error', 'manager', 'shop',array('view'=>'orderalreadypaid'));
			die();
		}
		
		$this->view->identity = "Process-".$orderId;
		
		$items = App_Model_Show_Order::show()->getOrderDetail($orderId);
		
		$tmpMethod = $this->_request->getParam('method');
		if(!empty($tmpMethod))
		{
			$items[0]['paymentMethod'] = $tmpMethod;
		}
		
		switch($items[0]['paymentMethod'])
		{
			case 'nsiapay' :
				
                require_once('PaymentGateway/Nsiapay.php');  
                $paymentObject = new Nsiapay;             
                
                if($this->_testMode){
                	$paymentObject->enableTestMode();
                }
                
                $paymentObject->addField('TYPE',"IMMEDIATE");
                
                $subTotal=0;
                
                for($iCart=0;$iCart<count($items);$iCart++)
                {
                	$i=$iCart+1;
                	$basket[] = $items[$iCart]['documentName'].",".$items[$iCart]['price'].".00".",".$items[$iCart]['qty'].",".$items[$iCart]['finalPrice'].".00";
                	$subTotal += $items[$iCart]['price'] * $items[$iCart]['qty'];              
                }
                
                $ca = implode(";", $basket);
                
                //$merchantId = "000100090000028";   development
                $merchantId = "000100013001060";
                
                $paymentObject->addField("BASKET",$ca);
                $paymentObject->addField("MERCHANTID",$merchantId);
                $paymentObject->addField("CHAINNUM","NA");
                $paymentObject->addField("TRANSIDMERCHANT",$items[0]['invoiceNumber']);
                $paymentObject->addField("AMOUNT",$subTotal);
                $paymentObject->addField("CURRENCY","360");
                $paymentObject->addField("PurchaseCurrency","360");
                $paymentObject->addField("acquirerBIN","360");
                $paymentObject->addField("password","123456");
                $paymentObject->addField("URL",ROOT_URL);
                //$paymentObject->addField("MALLID","199");   development
                $paymentObject->addField("MALLID","332");
                $paymentObject->addField("SESSIONID",Zend_Session::getId());
                $sha1 = sha1($subTotal.".00".$merchantId."08iIWbWvO16w".$items[0]['invoiceNumber']);
//                echo $subTotal.".00".$merchantId."08iIWbWvO16w".$items[0]['invoiceNumber']."<br>";
//                echo $sha1;die;
                $paymentObject->addField("WORDS",$sha1);
                
                $ivnum = $this->updateInvoiceMethod($orderId, 'nsiapay', 1, 0, 'paid with nsiapay method');
                
                $data['orderId'] = $orderId;
                $data['starttime'] = date('YmdHis');
                $data['amount'] = $subTotal;
                $data['transidmerchant'] = $items[0]['invoiceNumber'];
                
                $tblNsiapay = new App_Model_Db_Table_Nsiapay();
                $rowNsia = $tblNsiapay->fetchRow("transidmerchant='".$items[0]['invoiceNumber']."'");
                
                if (!$rowNsia) {
	                $id = $tblNsiapay->insert($data);
	                
	                $nhis['nsiaId'] = $id;
	                $nhis['paymentStatus'] = 'requested';
	                $nhis['dateAdded'] = date('YmdHis');
	                $tblNhis = new App_Model_Db_Table_NsiapayHistory();
	                $tblNhis->insert($nhis);
                }
                else 
                {
	                $nhis['nsiaId'] = $rowNsia->nsiaId;
	                $nhis['paymentStatus'] = 'requested';
	                $nhis['dateAdded'] = date('YmdHis');
	                $tblNhis = new App_Model_Db_Table_NsiapayHistory();
	                $tblNhis->insert($nhis);
                }
                
                //$paymentObject->dumpFields();die();
                $this->_helper->layout->disableLayout();
                
                $paymentObject->submitPayment();
                
				break;
				
			case 'bank':
				
				$this->updateInvoiceMethod($orderId, 'bank', 1, 0, 'paid with manual method');
				
				$this->_helper->redirector('instruction','payment','shop',array('orderId'=>$orderId));
				
				break;
		}
	}
	function listAction()
	{
		$this->_checkAuth();
		
        $where=$this->_user->kopel;

        $rowsetTotal = App_Model_Show_Order::show()->countOrders("'".$where."'");
        $rowset = App_Model_Show_Order::show()->getOrderSummary("'".$where."'");

        $this->view->numCount = $rowsetTotal;
        $this->view->listOrder = $rowset;
        
        $this->view->identity = "Sejarah Pesanan";
	}
	public function billingAction()
	{
        $this->_checkAuth();

        $rowset = App_Model_Show_UserFinance::show()->getUserFinance($this->_user->kopel);
        $this->view->rowset = $rowset;

        $outstandingAmount = App_Model_Show_Order::show()->outstandingUserAmout($this->_userFinanceInfo->userId);
        $this->view->outstandingAmount = $outstandingAmount;

        if($this->_request->isPost('save')){
            $data['taxNumber'] = $this->_request->getParam('taxNumber');
            $data['taxCompany'] = $this->_request->getParam('taxCompany');
            $data['taxAddress'] = $this->_request->getParam('taxAddress');
            $data['taxCity'] = $this->_request->getParam('taxCity');
            $data['taxProvince'] = $this->_request->getParam('province');
            $data['taxZip'] = $this->_request->getParam('taxZip');
            $data['taxPhone'] = $this->_request->getParam('taxPhone');
            $data['taxFax'] = $this->_request->getParam('taxFax');
            $data['taxCountryId'] = $this->_request->getParam('taxCountryId');
            $where = "userId = '".$this->_user->kopel."'";
            
            $userFinance = new App_Model_Db_Table_UserFinance();
            $userFinance->update($data,$where);
            
            $this->_helper->redirector('bilupdsucc');
        }
	}
    public function bilupdsuccAction()
    {
        $this->_checkAuth();
        $this->_redirect(ROOT_URL.'/shop/payment/billing');
    }
    public function transactionAction()
    {
        $this->_checkAuth();

        $where=$this->_user->kopel;

        $rowsetTotal = App_Model_Show_Order::show()->countOrders("'".$where."' AND (orderStatus = 3 OR orderStatus = 5)");
        $rowset = App_Model_Show_Order::show()->getOrderSummary("'".$where."' AND (orderStatus = 3 OR orderStatus = 5)");

        $this->view->numCount = $rowsetTotal;
        $this->view->listOrder = $rowset;
        
        $this->view->identity = "Sejarah Transaksi";
    }
    public function documentAction()
    {
        $this->_checkAuth();

        $userId = $this->_userFinanceInfo->userId;

        $rowset = App_Model_Show_Order::show()->getDocumentSummary($userId);
        $rowsetTotal = App_Model_Show_Order::show()->countDocument($userId);

        $this->view->numCount = $rowsetTotal;
        $this->view->rowset = $rowset;
    }
    public function confirmAction()
    {
        $this->_checkAuth();
        
        $userId = $this->_user->kopel;

        $rowset = App_Model_Show_Order::show()->getTransactionToConfirm($userId);
        $numCount = App_Model_Show_Order::show()->getTransactionToConfirmCount($userId);

        $modelPaymentSetting = new App_Model_Db_Table_PaymentSetting();
        $bankAccount = $modelPaymentSetting->fetchAll("settingKey = 'bankAccount'");

        if($this->_request->get('sended') == 1){
            $this->view->sended = 'Payment Confirmation Sent';
        }

        $this->view->numCount = $numCount;
        $this->view->rowset = $rowset;
        $this->view->bankAccount = $bankAccount;
        
        $this->view->identity = "Konfirmasi Pembayaran";
    }
    public function payconfirmAction()
    {
        $this->_checkAuth();

        $tmpOrderId = $this->_request->getParam('orderId');

        if(empty($tmpOrderId))
        {
            $this->_helper->redirector->gotoSimple('error', 'manager', 'shop',array('view'=>'noorderfound'));
            die();
        }

        $modelAppStore = new App_Model_Store();
        foreach($this->_request->getParam('orderId') as $key=>$value)
        {
            if(!$modelAppStore->isUserOwnOrder($this->_user->kopel, $value))
            {
                $this->_helper->redirector->gotoSimple('error', 'manager', 'shop',array('view'=>'notowner'));
                die();
            }
        }

        $tblConfirm = new App_Model_Db_Table_PaymentConfirmation();
        $tblOrder = new App_Model_Db_Table_Order();
        $r = $this->getRequest();

        $amount = 0;

        foreach($r->getParam('orderId') as $ksy=>$value){
            $amount += App_Model_Show_Order::show()->getAmount($value);
        }
        foreach($r->getParam('orderId')as $key=>$row)
        {
            $data = $tblConfirm->fetchNew();

            $data['paymentMethod'] = $r->getParam('paymentMethod');
            $data['destinationAccount'] = $r->getParam('destinationAccount');
            //$data['paymentDate'] = $r->getParam('paymentDate');
            $data['paymentDate'] = date("Y-m-d H:i:s");
            $data['amount'] = $amount;
            $data['currency'] = $r->getParam('currency');
            $data['senderAccount'] = $r->getParam('senderAccount');
            $data['senderAccountName'] = $r->getParam('senderAccountName');
            $data['bankName'] = $r->getParam('bankName');
            $data['note'] = $r->getParam('note');
            $data['orderId'] = $row;
            $data->save();

            $statdata['orderStatus'] = 4;
            $tblOrder->update($statdata, 'orderId = '.$data['orderId']);

            $tblHistory = new App_Model_Db_Table_OrderHistory();

            //add history
            $dataHistory = $tblHistory->fetchNew();
            //history data
            $dataHistory['orderId'] = $data['orderId'];

            $dataHistory['orderStatusId'] = 6;
            $dataHistory['dateCreated'] = date('Y-m-d');
            $dataHistory['userNotified'] = 1;
            $dataHistory['note'] = 'Waiting Confirmation';
            $dataHistory->save();

            $mod = new App_Model_Store_Mailer();
            $mod->sendUserBankConfirmationToAdmin($data['orderId']);
        }
        
        $this->_helper->redirector->gotoSimple('confirm', 'payment', 'shop', array('sended' => '1'));
    }
	function viewinvoiceAction()
	{
		$this->_checkAuth();
		
		$orderId = $this->_request->getParam('orderId');
		
		$items = App_Model_Show_Order::show()->getOrderDetail($orderId);
		
		$this->view->orderId = $orderId;
		$this->view->invoiceNumber = $items[0]['invoiceNumber'];
		$this->view->datePurchased = Pandamp_Lib_Formater::get_date($items[0]['datePurchased']);
		
		$tblPaymentSetting = new App_Model_Db_Table_PaymentSetting();        
        $rowTaxRate = $tblPaymentSetting->fetchRow("settingKey='taxRate'");
		
		if($this->_user->kopel != $items[0]['userId'])
		{
			$this->_redirect(ROOT_URL.'/shop/payment/cartempty');
		}
		
		$result = array();
		$result['subTotal'] = 0;
		for($iCart=0;$iCart<count($items);$iCart++){
            
			$itemId = $items[$iCart]['itemId'];
            $qty= 1;
            $itemPrice = $items[$iCart]['price'];
            
            $result['items'][$iCart]['itemId']= $itemId;
            $result['items'][$iCart]['item_name'] = $items[$iCart]['documentName']; 
            $result['items'][$iCart]['itemPrice']= $itemPrice;
            $result['items'][$iCart]['qty']= $qty;
            $result['subTotal'] += $itemPrice*$qty;
        }

		$result['taxAmount']= $result['subTotal'] * $rowTaxRate->settingValue/100;
        $result['grandTotal'] = $result['subTotal'] + $result['taxAmount'];

		$this->view->cart = $result;
		
		$data = array();
		$data['taxNumber'] = $items[0]['taxNumber'];
		$data['taxCompany'] = $items[0]['taxCompany'];
		$data['taxAddress'] = $items[0]['taxAddress'];
		$data['taxCity'] = $items[0]['taxCity'];
		$data['taxZip'] = $items[0]['taxZip'];
		$data['taxProvince'] = $items[0]['taxProvince'];
		$data['taxCountry'] = $items[0]['taxCountryId'];
		$data['paymentMethod'] = $items[0]['paymentMethod'];
		$data['currencyValue'] = $items[0]['currencyValue'];
		
		$this->view->data = $data;
		
		$this->view->identity = "Lihat Faktur-".$orderId;
	}
	public function cartemptyAction()
	{
		$this->view->identity = "Kartu Belanja Kosong";
	}
    public function instructionAction()
    {
		
		$orderId = $this->_request->getParam('orderId');
		
		$tblOrder = new App_Model_Db_Table_Order();
		$row = $tblOrder->find($orderId)->current();
		if(empty($row))
			die('NO ORDER DATA AVAILABLE');
			
		
		$this->view->row = $row;
		
		$_SESSION['jCart'] = null;         
    }
	protected function updateInvoiceMethod($orderId, $payMethod, $status, $notify, $note)
	{        
        $tblOrder = new App_Model_Db_Table_Order();
		
		$rows = $tblOrder->find($orderId)->current();
		$row = array();
		
		$ivnum = $rows->invoiceNumber;
		
		$row=array ('orderStatus' => $status, 'paymentMethod' => $payMethod);
		
		$tblOrder->update($row, 'orderId = '. $orderId);
		
		$tblHistory = new App_Model_Db_Table_OrderHistory();
		$rowHistory = $tblHistory->fetchNew();
		
		$rowHistory->orderId = $orderId;
		$rowHistory->orderStatusId = $status;
		$rowHistory->dateCreated = date('YmdHis');
		$rowHistory->userNotified = $notify;
		$rowHistory->note = $note;
		$rowHistory->save();
		
		return $ivnum;
	}
    private function _checkAuth()
    {
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity())
        {
	        $sReturn = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	        $sReturn = base64_encode($sReturn);
	
			$identity = Pandamp_Application::getResource('identity');
			$loginUrl = $identity->loginUrl;

			$this->_redirect($loginUrl.'?returnTo='.$sReturn);     
            
        }
        else
        {
            $this->_user = $auth->getIdentity();
        }

        $modelUserFinance = new App_Model_Db_Table_UserFinance();
        $this->_userFinanceInfo = $modelUserFinance->find($this->_user->kopel)->current();
        if (empty($this->_userFinanceInfo))
        {
            $finance = $modelUserFinance->fetchNew();
            $finance['userId'] = $this->_user->kopel;
            $finance->save();
            $this->_userFinanceInfo = $modelUserFinance->find($this->_user->kopel)->current();
        }
    }
}