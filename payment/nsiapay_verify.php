<?php 
error_reporting(E_ALL|E_STRICT);
require_once "../baseinit.php";


Pandamp_Application::getResource('session');
Pandamp_Application::getResource('multidb')->getDb('db1');
	


$transidmerchant = $_GET['TRANSIDMERCHANT'];
$currency = $_GET['CURRENCY'];

require_once(ROOT_DIR.'/app/models/Db/Table/Order.php');
$tblOrder = new App_Model_Db_Table_Order();
$rowOrder = $tblOrder->fetchRow("invoiceNumber='".$transidmerchant."'");

$datenow = date('YmdHis');

//if ($_SERVER['REMOTE_ADDR'] == '202.182.62.118') {
	
	if ($rowOrder) {
		
		//$rowOrder->orderStatus = 9;
		$rowOrder->datePurchased = $datenow;
			
		$rowOrder->save();
		
		$tblNsiapay = new App_Model_Db_Table_Nsiapay();
		$tblNsiapay->update(array('status'=>'verify','bin'=>$currency),"transidmerchant='".$transidmerchant."'");
		
		$tblNhis = new App_Model_Db_Table_NsiapayHistory();
		$tblNhis->insert(array('orderId'=>$rowOrder->orderId,'paymentStatus'=>'verify','dateAdded'=>date('YmdHis')));
		
		$response = "continue";
	}
	else
	{
		$response = "stop";
	}
		
	echo $response;
		
//}
//else
//{
//	$rowOrder->orderStatus = 7;
//	$rowOrder->datePurchased = $datenow;
//		
//	$rowOrder->save();
//		
//	echo "continue";
//}




?>