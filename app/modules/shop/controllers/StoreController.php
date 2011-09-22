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

        $cart = null;

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
                $mod->sendInvoiceToUser($orderId);
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
    function printpdfinvoiceAction()
    {
        $orderId = $this->_getParam('orderId');

        $this->_helper->layout->disableLayout();

        $items = App_Model_Show_Order::show()->getOrderDetail($orderId);
        
        // create new PDF document
        $pdf = new Pandamp_Lib_Pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        define ("PDF_HEADER_TITLE", "PT. Justika Siar Publika");
        define ("PDF_HEADER_STRING", "Puri Imperium Office Plaza, Jl. Kuningan Madya Kav 5-6 Kuningan Jakarta 12980,\nTelepon: (62-21) 83701827 / Faksimili: (62-21) 83701826\nE-mail: layanan@hukumonline.com");

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nihki Prihadi');
        $pdf->SetTitle('TCPDF Example');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, tutorial');

        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        //set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        //set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //set some language-dependent strings
        //$pdf->setLanguageArray($l);

        // ---------------------------------------------------------

        // set default font subsetting mode
        $pdf->setFontSubsetting(true);

        // Set font
        // dejavusans is a UTF-8 Unicode font, if you only need to
        // print standard ASCII chars, you can use core fonts like
        // helvetica or times to reduce file size.
        $pdf->SetFont('dejavusans', '', 10, '', true);


        // add a page
        $pdf->AddPage();

        // create address box
        $pdf->CreateTextBox('Kepada Yth,', 0, 25, 80, 10, 10, 'B');
        $pdf->CreateTextBox($items[0]['taxCompany'], 0, 30, 80, 10, 10);

        if ($items[0]['orderStatus'] == 3) {
            $status = "LUNAS";
        }
        else
        {
            $status = "BELUM LUNAS";
        }
        $html='
            <table>
            <tr>
                <td style="color:red;font-size:3em;">'.$status.'</td>
            </tr>
            </table>
            ';
                $pdf->writeHTMLCell($w=0, $h=0, 90, 30, $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

        $pdf->CreateTextBox('ATTN: '.$this->_user->fullName, 0, 35, 80, 10, 10);
        $pdf->CreateTextBox($items[0]['taxAddress'], 0, 40, 80, 10, 10);
        $pdf->CreateTextBox($items[0]['taxCity'] . '-' . $items[0]['taxZip'], 0, 45, 80, 10, 10);

        $modelProvince = new App_Model_Db_Table_Province();
        $rowProvince = $modelProvince->find($items[0]['taxProvince'])->current();
        if ($rowProvince) $pdf->CreateTextBox($rowProvince->pname, 0, 50, 80, 10, 10);

        // invoice title / number
        $pdf->CreateTextBox('Invoice #'.$items[0]['invoiceNumber'], 0, 65, 120, 20, 16);

        // date, order ref
        $pdf->CreateTextBox('Date: '.  Pandamp_Lib_Formater::get_date($items[0]['datePurchased']), 0, 75, 0, 10, 10, '', 'R');
        $pdf->CreateTextBox('Order ref.: #'.$items[0]['orderId'], 0, 80, 0, 10, 10, '', 'R');

        // list headers
        $pdf->CreateTextBox('Quantity', 0, 95, 20, 10, 10, 'B', 'C');
        $pdf->CreateTextBox('Product or service', 20, 95, 90, 10, 10, 'B');
        $pdf->CreateTextBox('Price', 110, 95, 30, 10, 10, 'B', 'R');
        $pdf->CreateTextBox('Amount', 140, 95, 30, 10, 10, 'B', 'R');

        $pdf->Line(20, 105, 195, 105);

        $currY = 108;
        $total = 0;

        for($iCart=0;$iCart<count($items);$iCart++) {
                $pdf->CreateTextBox($items[$iCart]['qty'], 0, $currY, 20, 10, 10, '', 'C');
        $html2='
            <table>
            <tr>
                <td>'.$items[$iCart]['documentName'].'</td>
            </tr>
            </table>
            ';
                $pdf->writeHTMLCell(90, 10, 40, $currY, $html2, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

        //	$pdf->CreateTextBox($items[$iCart]['documentName'], 20, $currY, 90, 10, 10, '');
                $pdf->CreateTextBox('Rp '.number_format($items[$iCart]['price'],0,',','.'), 110, $currY, 30, 10, 10, '', 'R');
                $amount = $items[$iCart]['price'] * $items[$iCart]['qty'];
                $pdf->CreateTextBox('Rp '.number_format($items[$iCart]['finalPrice'],0,',','.'), 140, $currY, 30, 10, 10, '', 'R');
                $currY = $currY+15;
                $total = $total+$amount;
        }
        $pdf->Line(195, $currY+8, 130, $currY+8);

        // output the total row
        $pdf->CreateTextBox('Sub Total ', 5, $currY+7, 135, 10, 10, 'B', 'R');
        $pdf->CreateTextBox('Rp '.number_format($total, 2, ',', '.'), 140, $currY+7, 30, 10, 10, 'B', 'R');
        if ($items[0]['discount'] > 0)
        {
            $pdf->CreateTextBox('Disc ', 5, $currY+12, 135, 10, 10, 'B', 'R');
            $pdf->CreateTextBox($items[0]['discount'].'%', 140, $currY+12, 30, 10, 10, 'B', 'R');
            $pdf->CreateTextBox('Grand Total ', 5, $currY+22, 135, 10, 10, 'B', 'R');
            $grandTotal = ($result['subTotal'] - ($result['disc']/100 * $result['subTotal']));
            $total = ($total - ($items[0]['discount']/100 * $total));
            $pdf->CreateTextBox('Rp '.number_format($total, 2, ',', '.'), 140, $currY+22, 30, 10, 10, 'B', 'R');
        }
        else
        {
            $pdf->CreateTextBox('Grand Total ', 5, $currY+12, 135, 10, 10, 'B', 'R');
            $pdf->CreateTextBox('Rp '.number_format($total, 2, ',', '.'), 140, $currY+12, 30, 10, 10, 'B', 'R');
        }

        // some payment instructions or information
        $pdf->setXY(20, $currY+50);
        $pdf->SetFont(PDF_FONT_NAME_MAIN, '', 10);
        $ft = '
            <b>Pembayaran lewat transfer bank</b><br/>
            1. Bank BNI 46, Cabang Dukuh Bawah, No. 0073957339, a/n PT Justika Siar Publika<br/>
            2. Bank BCA, Cabang Pembantu Menara Imperium, No. 221-3028-707, a/n PT Justika Siar Publika<br><br><br>
            Jika anda sudah melakukan pembayaran, mohon konfirmasikan pembayaran anda lewat situs kami di '.ROOT_URL.'/payment/confirm atau email ke layanan@hukumonline.com<br>
            atau fax ke (021) 8370-1826<br><br>
            Terima kasih atas kepercayaan anda atas hukumonline.com.<br><br>
            Salam,<br>
            hukumonline.com
            ';
        $pdf->MultiCell(175, 10, $ft, 0, 'L', 0, 1, '', '', true, null, true);

        //Close and output PDF document
        $pdf->Output('Hukumonline_EStore_Report.pdf', 'I');
    }
}