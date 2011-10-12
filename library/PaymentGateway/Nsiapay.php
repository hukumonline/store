<?php

include_once('PaymentGateway.php');

class Nsiapay extends PaymentGateway
{
	public function __construct()
	{
		parent::__construct();
		
		//$this->gatewayUrl = 'https://www.nsiapay.com/ipg_payment/RegisterOrderInfo';
		//$this->gatewayUrl = 'https://pay.doku.com/ipg_payment/RegisterOrderInfo';
		$this->gatewayUrl = 'https://pay.doku.com/DokuSuite/Channel';
		$this->ipnLogFile = 'nsiapay.ipn_results.log';
	}
    public function enableTestMode()
    {
        $this->testMode = TRUE;
        $this->gatewayUrl = 'http://luna.nsiapay.com/ipg_payment/RegisterOrderInfo';
        
        $this->addField('demo', 'Y');
    }
    public function validateIpn() {}
}