<?php
class Membership_PaymentController extends Zend_Controller_Action 
{
	protected $_testMode;
	protected $_orderIdNumber;
	protected $_defaultCurrency;
	protected $_currencyValue;
	protected $_user;
	
	function preDispatch()
	{
		$sReturn = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$sReturn = base64_encode($sReturn);
		
		$auth = Zend_Auth::getInstance();
		if (!$auth->hasIdentity())
		{
			$registry = Zend_Registry::getInstance();
			$config = $registry->get('config');
			
			$loginUrl = $config->identity->config->local->login->url;
						
			$this->_redirect(ROOT_URL.$loginUrl.'?returnTo='.$sReturn);
		}
		else 
		{
	        $this->_testMode=true;
			$this->_defaultCurrency='USD';
			$tblPaymentSetting = new App_Model_Db_Table_PaymentSetting();
			$usdIdrEx = $tblPaymentSetting->fetchAll($tblPaymentSetting->select()->where(" settingKey= 'USDIDR'"));
			$this->_currencyValue = $usdIdrEx[0]->settingValue;
			
			$this->_helper->layout->setLayout('layout-membership');
			$this->_helper->layout->setLayoutPath(array('layoutPath'=>ROOT_DIR.'/app/modules/membership/views/layouts'));
					
			Zend_Session::start();

            $this->_user = $auth->getIdentity();
		}
	}
	function completeAction()
	{
		$formater 	= new Pandamp_Core_Hol_User();
		
		$defaultCurrency = 'Rp';
		
		$kopel = $this->_request->getParam('kopel');
		$method = $this->_request->getParam('method');
		$packageId = $this->_request->getParam('packageId');
		$paymentSubscription = $this->_request->getParam('payment');
		
		$tblPaymentSetting = new App_Model_Db_Table_PaymentSetting();
		$usdIdrEx = $tblPaymentSetting->fetchRow(" settingKey= 'USDIDR'");
		$currencyValue = $usdIdrEx->settingValue;      
        $rowTaxRate = $tblPaymentSetting->fetchRow("settingKey='taxRate'");
		$taxRate = $rowTaxRate->settingValue;
		
		$tblUser = new App_Model_Db_Table_User();
		$rowUser = $tblUser->find($kopel)->current();
		
		/*
    	$modelGroup = new Kutu_Core_Orm_Table_Group();
    	$row = $modelGroup->fetchRow("id=".$this->_user->packageId);
		if ($row->name == "free") {
			$rowUser->periodeId = 2;
			$rowUser->save();
		}
		*/
		
		$this->view->rowUser = $rowUser;
		
		// discount
		$disc = $formater->checkPromoValidation('Disc',$packageId,$rowUser->promotionId,$paymentSubscription);
		$total = $formater->checkPromoValidation('Total',$packageId,$rowUser->promotionId,$paymentSubscription);
		
		$tblPackage = new App_Model_Db_Table_Package();
		$rowPackage = $tblPackage->fetchRow("packageId=$packageId");
		
		$this->view->rowPackage = $rowPackage;
		
		$tblOrder=new App_Model_Db_Table_Order();
        $row=$tblOrder->fetchNew();
		
		$row->userId=$kopel;
		
		if ($this->getRequest()->getPost()) {
			$value = $this->getRequest()->getPost(); 
				
			$row->taxNumber=$value['taxNumber'];
			$row->taxCompany=$value['taxCompany'];
			$row->taxAddress=$value['taxAddress'];
			$row->taxCity=$value['taxCity'];
			$row->taxZip=$value['taxZip'];
			$row->taxProvince=$value['taxProvince'];
			$row->taxCountryId=$value['taxCountry'];
			$row->paymentMethod=$method;
		}
		
        $row->datePurchased=date('YmdHis');
        $row->paymentMethodNote = "membership";
        
        if ($method == "nsiapay") {
        	$row->orderStatus=8;
        }
        else {
        	$row->orderStatus=1; //pending
        }
        
        $row->currency = $defaultCurrency;        
        $row->currencyValue = $currencyValue;    

        $row->orderTotal=$total;
        $row->ipAddress= Pandamp_Lib_Formater::getRealIpAddr();
        
        $orderId = $row->save();
        
        $rowJustInserted = $tblOrder->find($orderId)->current();
		$rowJustInserted->invoiceNumber = date('Ymd') . '.' . $orderId;
		
		$temptime = time();
		$temptime = Pandamp_Lib_Formater::DateAdd('d',5,$temptime);
			
		$rowJustInserted->discount = $disc;
		$rowJustInserted->invoiceExpirationDate = strftime('%Y-%m-%d',$temptime);
		
		$rowJustInserted->save();
		
		$this->view->invoiceNumber = $rowJustInserted->invoiceNumber;
		$this->view->datePurchased = $rowJustInserted->datePurchased;
        
		$tblOrderDetail=new App_Model_Db_Table_OrderDetail();
		$rowDetail=$tblOrderDetail->fetchNew();
		
		$rowDetail->orderId=$orderId;
		$rowDetail->itemId=$rowPackage->packageId;

    	$modelGroup = new App_Model_Db_Table_Group();
    	$row = $modelGroup->fetchRow("id=$packageId");

		$group = "Subsciption for Member ".ucwords(strtolower($row->name))." ".$paymentSubscription." Months";
		
		$this->view->packageId = $packageId;
		$this->view->paymentSubscription = $paymentSubscription;
		
		$this->view->itemName = $group;
		
		$rowDetail->documentName=$group;
		
		$rowDetail->price=$total;
		
		$numOfUsers = $tblUser->getUserCount($rowUser->kopel);
		
		$this->view->numOfUsers = $numOfUsers;
		$this->view->grandtotal = $total;
		$this->view->method = $method;
		$this->view->orderId = $orderId;
		$this->view->total = $rowPackage->charge;
		
		$rowDetail->qty=$numOfUsers;
		$rowDetail->finalPrice=$total;
		
		$rowDetail->save();
		
		$data = $this->_request->getParams();
		
		$this->view->data = $data;
		
		$modDir = $this->getFrontController()->getModuleDirectory();
		require_once($modDir.'/models/Store/Mailer.php');
		$mod = new Membership_Model_Store_Mailer();
		
		switch(strtolower($method))
		{
			case 'manual':
			case 'bank':
				$mod->sendBankInvoiceToUser($orderId);
				break;
			case 'nsiapay':
				$mod->sendInvoiceToUser($orderId);
				break;
		}
	}
	function processAction()
	{
		$formater 	= new Pandamp_Core_Hol_User();
		
		$orderId = $this->_request->getParam('orderId');
		$packageId = $this->_request->getParam('packageId');
		$paymentSubscription = $this->_request->getParam('paymentSubscription');
		$this->_orderIdNumber = $orderId;
		
		if(empty($orderId))
		{
			echo "kosong";
			die();
		}
		
		include_once(ROOT_DIR.'/app/models/Store.php');
		$modelAppStore = new App_Model_Store();
		if($modelAppStore->isOrderPaid($orderId))
		{
			//forward to error page
			$this->_helper->redirector->gotoSimple('error', 'store', 'hol-site',array('view'=>'orderalreadypaid'));
			die();
		}
		
		$tblOrder = new App_Model_Db_Table_Order();
		$items = $tblOrder->getOrderDetail($orderId);
		
		$tmpMethod = $this->_request->getParam('method');
		if(!empty($tmpMethod))
		{
			$items[0]['paymentMethod'] = $tmpMethod;
		}
		
		$tblUser = new App_Model_Db_Table_User();
		$rowUser = $tblUser->find($items[0]['userId'])->current();
		
		$total = $formater->checkPromoValidation('Total',$packageId,$rowUser->promotionId,$paymentSubscription);
		
		switch($items[0]['paymentMethod'])
		{
			case 'nsiapay' :
				
                require_once('PaymentGateway/Nsiapay.php');  // include the class file
                $paymentObject = new Nsiapay;             // initiate an instance of the class
                
                if($this->_testMode){
                	$paymentObject->enableTestMode();
                }
                
                $paymentObject->addField('TYPE',"IMMEDIATE");
                $subTotal = 0;
                for($iCart=0;$iCart<count($items);$iCart++)
                {
                	$i=$iCart+1;
                	$basket[] = $items[$iCart]['documentName'].",".$items[$iCart]['price'].".00".",".$items[$iCart]['qty'].",".$items[$iCart]['finalPrice'].".00";
                	$subTotal += $items[$iCart]['price'] * $items[$iCart]['qty'];              
                }
                
                $ca = implode(";", $basket);
                
                $merchantId = "000100090000028";
                
                $paymentObject->addField("BASKET",$ca);
                $paymentObject->addField("MERCHANTID",$merchantId);
                $paymentObject->addField("CHAINNUM","NA");
                $paymentObject->addField("TRANSIDMERCHANT",$items[0]['invoiceNumber']);
                $paymentObject->addField("AMOUNT",$subTotal);
                $paymentObject->addField("CURRENCY","360");
                $paymentObject->addField("PurchaseCurrency","360");
                $paymentObject->addField("acquirerBIN","360");
                $paymentObject->addField("password","123456");
                $paymentObject->addField("URL","http://hukumonline.pl");
                $paymentObject->addField("MALLID","199");
                $paymentObject->addField("SESSIONID",Zend_Session::getId());
                $sha1 = sha1($subTotal.".00".$merchantId."08iIWbWvO16w".$items[0]['invoiceNumber']);
//                echo $subTotal.".00".$merchantId."08iIWbWvO16w".$items[0]['invoiceNumber']."<br>";
//                echo $sha1;die;
                $paymentObject->addField("WORDS",$sha1);
                
                //$paymentObject->dumpFields();
                $this->_helper->layout->disableLayout();
                
                $paymentObject->submitPayment();
                
				break;
			case 'manual':
			case 'bank':
                /*
                 1. update order status
                 2. redirect to instruction page 
                */

				//setting payment and status as pending (1), notify = 0, notes = 'paid with...'
				$this->updateInvoiceMethod($orderId, 'bank', 1, 0, 'paid with manual method');
				
				// HAP: i think we should send this notification when user were on page "Complete Order" and after confirmation made by user is approved;
				//$this->Mailer($orderId, 'admin-order', 'admin');
				//$this->Mailer($orderId, 'user-order', 'user');
				
				$this->_helper->redirector('instruction','payment','membership',array('orderId'=>$orderId));
                break;
				
		}
	}
	protected function updateInvoiceMethod($orderId, $payMethod, $status, $notify, $note){        
        $tblOrder = new App_Model_Db_Table_Order();
		
		$rows = $tblOrder->find($orderId)->current();
		$row = array();
		
		$ivnum = $rows->invoiceNumber;
		
		/*if(empty($ivnum)){
			if($status==3 || $status==5 || (!empty($_SESSION['_method'])&&($_SESSION['_method'] =='paypal')))
			$ivnum = $this->getInvoiceNumber();
			//$row=array ('invoiceNumber'	=> $ivnum);
		}*/
		//if( )$ivnum = $this->getInvoiceNumber();
		
		
		$row=array ('orderStatus'	=> $status, 'paymentMethod' => $payMethod);
		
		//$_SESSION['_method'] = '';
		/*$this->_paymentMethod=$payMethod;//set payment method on table
		$row->paymentMethod=$this->_paymentMethod;*/
		
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
    public function instructionAction(){
		
		$orderId = $this->_request->getParam('orderId');
		
		$tblOrder = new App_Model_Db_Table_Order();
		$row = $tblOrder->find($orderId)->current();
		if(empty($row))
			die('NO ORDER DATA AVAILABLE');
			
		//var_dump($rowset);
		
		$this->view->row = $row;
		
		$_SESSION['jCart'] = null;         
    }
    
}