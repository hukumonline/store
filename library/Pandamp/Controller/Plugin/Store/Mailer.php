<?php
class Pandamp_Controller_Plugin_Store_Mailer
{
	function sendBankInvoiceToUser($orderId)
	{
		$config = new Zend_Config_Ini(CONFIG_PATH.'/mail.ini', 'mail');
		
		$siteOwner = "Hukumonline";
		$siteName = $config->mail->sender->support->name;
		$contactEmail = $config->mail->sender->support->email;
		
		$tblOrder = new App_Model_Db_Table_Order();
		$rowOrder = $tblOrder->find($orderId)->current();
		$userId = $rowOrder->userId;
		
		$tblUser = new App_Model_Db_Table_User();
		$rowUser = $tblUser->find($userId)->current();
		
		$userEmail = $rowUser->email;
		$userFullname = $rowUser->fullName;
		
		$datePurchased = Pandamp_Lib_Formater::get_date($rowOrder->datePurchased);
		
		if ($rowOrder->paymentMethodNote == "membership")
			$duedate = date("d/m/y",strtotime($rowOrder->invoiceExpirationDate));
		else
			$duedate = "-";
			
		
		
		$message = 
"Kepada Yth, 
$userFullname

Berikut kami beritahukan bahwa Invoice untuk anda sudah dibuat pada tanggal $datePurchased.

Dengan metode pembayaran: $rowOrder->paymentMethod

Untuk pembayaran dengan Transfer Bank anda bisa memilih lima opsi berikut:

BCA BANK, cabang Menara Imperium
No. Rek: 221-3028-707
PT. Justika Siar Publika

BANK BNI, cabang Dukuh Bawah
No. Rek: 0073957339
PT. Justika Siar Publika

Invoice # $rowOrder->invoiceNumber
Jumlah tagihan: Rp. $rowOrder->orderTotal
Jatuh tempo: $duedate

Anda bisa login di Ruang Konsumen untuk melihat status invoice ini atau melakukan pembayaran secara online di ".ROOT_URL."/store/viewinvoice/orderId/$orderId. Setelah melakukan pembayaran lewat transfer bank, mohon segera melakukan konfirmasi pembayaran anda lewat ".ROOT_URL."/store_payment/confirm

Salam,

Hukumonline
==============================";	

		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
				$userEmail, '', "Hukumonline Invoice: ". $rowOrder->invoiceNumber, $message);
	
	}
	function sendInvoiceToUser($orderId)
	{
		$config = new Zend_Config_Ini(CONFIG_PATH.'/mail.ini', 'mail');
		
		$siteOwner = "Hukumonline";
		$siteName = $config->mail->sender->support->name;
		$contactEmail = $config->mail->sender->support->email;
		
		$tblOrder = new App_Model_Db_Table_Order();
		$rowOrder = $tblOrder->find($orderId)->current();
		$userId = $rowOrder->userId;
		
		$tblUser = new App_Model_Db_Table_User();
		$rowUser = $tblUser->find($userId)->current();
		
		$userEmail = $rowUser->email;
		$userFullname = $rowUser->fullName;
		
		$datePurchased = Pandamp_Lib_Formater::get_date($rowOrder->datePurchased);
		
		if ($rowOrder->paymentMethodNote == "membership")
			$duedate = date("d/m/y",strtotime($rowOrder->invoiceExpirationDate));
		else
			$duedate = "-";
			
		
		$message = 
"Kepada Yth, 
$userFullname

Berikut kami beritahukan bahwa Invoice untuk anda sudah dibuat pada tanggal $datePurchased.

Dengan metode pembayaran: $rowOrder->paymentMethod

Invoice # $rowOrder->invoiceNumber
Jumlah tagihan: Rp. $rowOrder->orderTotal
Jatuh tempo: $duedate

Anda bisa login di Ruang Konsumen untuk melihat status invoice ini atau melakukan pembayaran secara online di ".ROOT_URL."/store/viewinvoice/orderId/$orderId.

Terima kasih,

Hukumonline

==============================";	

		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
				$userEmail, '', "Hukumonline Invoice: ". $rowOrder->invoiceNumber, $message);
	
	}
	public function sendReceiptToUser($orderId, $paymentMethod='', $statusText='')
	{
		$config = new Zend_Config_Ini(CONFIG_PATH.'/mail.ini', 'general');
		
		$siteOwner = "Hukumonline";
		$siteName = $config->mail->sender->support->name;
		$contactEmail = $config->mail->sender->support->email;
		
		$tblOrder = new App_Model_Db_Table_Order();
		$rowOrder = $tblOrder->find($orderId)->current();
		$userId = $rowOrder->userId;
		
		//first check if orderId status is PAID, then send the email.
		
		switch ($rowOrder->orderStatus)
		{
			case 1:
				die('ORDER STATUS IS NOT YET PAID. CAN NOT SEND RECEIPT!.');
				break;
			case 3:
				$orderStatus = "PAID";
				break;
			case 5:
				$orderStatus = "POSTPAID PENDING";
				break;
			case 6:
				$orderStatus = "PAYMENT REJECTED";
				break;
			case 7:
				$orderStatus = "PAYMENT ERROR";
				break;
			default:
				$orderStatus = "PAYMENT ERROR";
				break;
		}
		
		$tblUser = new App_Model_Db_Table_User();
		$rowUser = $tblUser->find($userId)->current();
		
		$userEmail = $rowUser->email;
		$userFullname = $rowUser->fullName;
		
		switch(strtolower($paymentMethod))
		{
			case 'paypal':
			case 'manual':
			case 'bank':
			case 'postpaid':
			default:
				$message = 
"Kepada Yth, 
$userFullname,

Ini adalah bukti pembayaran untuk faktur # $rowOrder->invoiceNumber

Total Jumlah: Rp $rowOrder->orderTotal
Transaksi #:
Jumlah Dibayar: Rp $rowOrder->orderTotal
Status: $orderStatus
Metode Pembayaran: $paymentMethod

Anda dapat meninjau riwayat faktur Anda setiap saat dengan log in ke account Anda ".ROOT_URL."/shop/payment/list

Catatan: Email ini akan berfungsi sebagai tanda terima resmi untuk pembayaran ini.

Salam,

Hukumonline

==============================";

		}
		
		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
				$userEmail, '', "Hukumonline Receipt Invoice# ". $rowOrder->invoiceNumber, $message);
	}
	
	public function sendUserBankConfirmationToAdmin($orderId)
	{
		$config = new Zend_Config_Ini(CONFIG_PATH.'/mail.ini', 'mail');
		
		$registry = Zend_Registry::getInstance();
		$config = $registry->get(Pandamp_Keys::REGISTRY_APP_OBJECT);
		$url = $config->getOption('f');
		
		$sOrderId = '';
		
		if(is_array($orderId))
		{
			
			for($i=0; $i< count($orderId);$i++)
			{
				$sOrderId .= $orderId[$i].', ';
			}
		}
		else
		{
			$sOrderId = $orderId;
		}
		
		$message = 
"					
Anda baru saja menerima konfirmasi pembayaran untuk pemesanan dengan ID $sOrderId harap login ke admin hukumonline.".

$url['remote']['url']."/id/store/confirm.

==============================";


		$this->send($config->mail->sender->support->email, $config->mail->sender->support->name, 
						$config->mail->sender->billing->email, $config->mail->sender->billing->name, 
						"[HUKUMONLINE] Bank Transfer Payment Confirmation ", $message);
		
		
	}
	
	public function send($mailFrom, $fromName, $mailTo, $mailToName, $subject, $body)
	{
		$config = new Zend_Config_Ini(CONFIG_PATH.'/mail.ini', 'mail');
		$options = array('auth' => $config->mail->auth,
		                'username' => $config->mail->username,
		                'password' => $config->mail->password);
		
		if(!empty($config->mail->ssl))
		{
			$options = array('auth' => $config->mail->auth,
							'ssl' => $config->mail->ssl,
			                'username' => $config->mail->username,
			                'password' => $config->mail->password);
		}
			
		$transport = new Zend_Mail_Transport_Smtp($config->mail->host, $options);
		
		$mail = new Zend_Mail();
		$mail->setBodyText($body);
		$mail->setFrom($mailFrom, $fromName);
		$mail->addTo($mailTo, $mailToName);
		$mail->setSubject($subject);
		
		try 
		{
			$mail->send($transport);
		}
		catch (Zend_Exception $e)
		{
			echo $e->getMessage();
		}
	}
	
}