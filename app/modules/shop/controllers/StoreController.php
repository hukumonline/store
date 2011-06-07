<?php

class Shop_StoreController extends Zend_Controller_Action 
{
	protected $_user;
	
	function preDispatch()
	{
		$this->_helper->layout->setLayout('layout-store-shipping');
		Zend_Session::start();
        $sReturn = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        $sReturn = base64_encode($sReturn);

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
	}
	function dropshippingAction()
	{
        if(!is_object($_SESSION['jCart']))
        {
            $this->_redirect(ROOT_URL.'/checkout/cartempty');
        }
        if(count($_SESSION['jCart']->items)==0)
        {
            $this->_redirect(ROOT_URL.'/checkout/cartempty');
        }

        $cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();
        $this->view->cart = $cart;
        
        $modelUser = new App_Model_Db_Table_User();
        $userDetailInfo = $modelUser->find($this->_user->kopel)->current();

        $this->view->userDetailInfo = $userDetailInfo;
        
        $modelUserFinance = new App_Model_Db_Table_UserFinance();
        $userFinanceInfo = $modelUserFinance->find($this->_user->kopel)->current();
        if(empty($userFinanceInfo))
        {
            $finance = $modelUserFinance->fetchNew();
            $finance->userId = $this->_user->kopel;
            $finance->taxCompany = $userDetailInfo->company;
            $finance->taxAddress = $userDetailInfo->address;
            $finance->taxProvince = $userDetailInfo->state;
            $finance->taxCountryId = $userDetailInfo->countryId;
            $finance->taxZip = $userDetailInfo->zip;
            $finance->taxPhone = $userDetailInfo->phone;
            $finance->taxFax = $userDetailInfo->fax;
            $finance->save();
        }
        
		$this->view->identity = "Data Pemesanan";
	}
	function confirmorderAction()
	{
        if(!is_object($_SESSION['jCart']))
        {
            $this->_redirect(ROOT_URL.'/checkout/cartempty');
        }
        if(count($_SESSION['jCart']->items)==0)
        {
            $this->_redirect(ROOT_URL.'/checkout/cartempty');
        }

        $cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();
        $this->view->cart = $cart;

        $data = array();
        foreach($this->_request->getParams() as $key=>$value){
            $data[$key] = $value;
        }

        $this->view->data = $data;
        
        $this->view->identity = "Konfirmasi Pemesanan";
	}
	function completeorderAction()
	{
        $modelPaymentSetting = new App_Model_Db_Table_PaymentSetting();
        $rowTaxRate = $modelPaymentSetting->fetchRow("settingKey='taxRate'");

        $cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();

        if(empty($cart) || count($cart->items)==0)
        {
            $this->_redirect(ROOT_URL.'/checkout/cartempty');
        }

        $bpm = new Pandamp_Core_Hol_Catalog();

        $result = array('subTotal' => 0, 'disc' => 0, 'taxAmount' => 0, 'grandTotal'=> 0,'items'=>array());
        for($iCart=0;$iCart<count($cart->items);$iCart++)
        {
            $itemId=$cart->items[$iCart];
            $qty= $cart->itemqtys[$itemId];
            $coupon = (isset($cart->coupon))?$cart->coupon:'';
            $itemPrice=$bpm->getPrice($itemId);
            $disc = $bpm->getDiscount($coupon);
            $result['items'][$iCart]['itemId']= $itemId;
            $result['items'][$iCart]['item_name'] = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($itemId,'fixedTitle');
            $result['items'][$iCart]['itemPrice']= $itemPrice;
            $result['items'][$iCart]['itemTotal']= $qty * $itemPrice;
            $result['items'][$iCart]['qty']= $qty;
            $result['coupon']=$coupon;
            $result['disc']=$disc;
            $result['subTotal']+=$itemPrice*$qty;
        }
        
        $result['taxAmount']= $result['subTotal']*$rowTaxRate->settingValue/100;
        $grandTotal = ($result['subTotal'] - ($result['disc']/100 * $result['subTotal']));
        $result['grandTotal'] = $grandTotal+$result['taxAmount'];

        $payment = $this->_request->getParam('payment');
        $orderId = $this->saveOrder($result, $payment);

//        $cart = null;

        $data = $this->_request->getParams();

        $this->view->cart = $result;
        $this->view->data = $data;
        $this->view->orderId = $orderId;
        
        $modDir = $this->getFrontController()->getModuleDirectory();
        require_once($modDir.'/models/Store/Mailer.php');
        $mod = new Shop_Model_Store_Mailer();

        switch(strtolower($payment['method']))
        {
            case 'bank':
                $mod->sendBankInvoiceToUser($orderId);
                break;
            case 'nsiapay':
//                $mod->sendInvoiceToUser($orderId);
                break;
            case 'postpaid':
                
                $tblUserFinance= new App_Model_Db_Table_UserFinance();
                $userFinanceInfo = $tblUserFinance->find($this->_user->kopel)->current();
                if(!$userFinanceInfo->isPostPaid){
                    return $this->_helper->redirector('notpostpaid','store_payment','hol-site');
                }

                $mod->sendInvoiceToUser($orderId);
                break;
        }
		
		$this->view->identity = "Menyelesaikan Pesanan";
	}
    private function saveOrder($cart,$payment)
    {
        $defaultCurrency='Rp';

        $tblPaymentSetting = new App_Model_Db_Table_PaymentSetting();
        $usdIdrEx = $tblPaymentSetting->fetchRow(" settingKey= 'USDIDR'");
        $currencyValue = $usdIdrEx->settingValue;
        $rowTaxRate = $tblPaymentSetting->fetchRow("settingKey='taxRate'");
        $taxRate = $rowTaxRate->settingValue;

        $tblOrder=new App_Model_Db_Table_Order();
        $row=$tblOrder->fetchNew();

        $row->userId=$this->_user->kopel;

        //get value from post var (store/checkout.phtml)
        if($this->getRequest()->getPost()){
            $value = $this->getRequest()->getPost();

            $row->taxNumber='';
            $row->taxCompany=$value['taxCompany'];
            $row->taxAddress=$value['taxAddress'];
            $row->taxCity=$value['taxCity'];
            $row->taxZip=$value['taxZip'];
            $row->taxProvince=$value['taxProvince'];
            $row->taxCountryId='';
            $row->paymentMethod=$payment['method'];
            $row->bankName=$payment['bank_options'];
        }

        $row->datePurchased=date('YmdHis');

        $row->orderStatus=1; //pending
        $row->currency = $defaultCurrency;
        $row->currencyValue = $currencyValue;
        $row->discount=$cart['disc'];
        $row->orderTotal=$cart['grandTotal'];
        $row->orderTax=$cart['taxAmount'];

        $row->ipAddress= Pandamp_Lib_Formater::getRealIpAddr();

        $orderId = $row->save();

        $rowJustInserted = $tblOrder->find($orderId)->current();
//      $rowJustInserted->invoiceNumber = date('Ymd') . '.' . $orderId;

        $tblNumber = new App_Model_Db_Table_GenerateNumber();
        $rowset = $tblNumber->fetchRow();
        $num = $rowset->invoice;
        $totdigit = 5;
        $num = strval($num);
        $jumdigit = strlen($num);
        $noinvoice = str_repeat("0",$totdigit-$jumdigit).$num;
        $rowset->invoice = $rowset->invoice += 1;
        $tblNumber->update(array('invoice'=>$rowset->invoice),NULL);

        $rowJustInserted->invoiceNumber = $noinvoice;
        $rowJustInserted->save();

        $this->view->invoiceNumber = $rowJustInserted->invoiceNumber;
        $this->view->datePurchased = $rowJustInserted->datePurchased;

        $tblOrderDetail=new App_Model_Db_Table_OrderDetail();

        for($iCart=0;$iCart<count($cart['items']);$iCart++){
            $rowDetail=$tblOrderDetail->fetchNew();

            $itemId=$cart['items'][$iCart]['itemId'];
            $rowDetail->orderId=$orderId;
            $rowDetail->itemId=$itemId;
            $rowDetail->documentName=$cart['items'][$iCart]['item_name'];
            $rowDetail->price=$cart['items'][$iCart]['itemPrice'];
            $itemPrice = $rowDetail->price;
            @$rowDetail->tax=((($cart['grandTotal']-$cart['subTotal']))/$cart['subTotal'])*100;
            $rowDetail->discount=$cart['disc'];
            $rowDetail->qty=$cart['items'][$iCart]['qty'];
            $rowDetail->finalPrice=$cart['items'][$iCart]['itemTotal'];
            $rowDetail->save();
        }

        return $orderId;

    }
}