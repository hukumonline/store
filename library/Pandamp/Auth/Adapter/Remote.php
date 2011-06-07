<?php
class Pandamp_Auth_Adapter_Remote implements Zend_Auth_Adapter_Interface
{
	private $_identity;
	private $_credential;
	private $_remoteAuthUrl;
	private $_resultRow;
	
	public function __construct($remoteAuthUrl,$identity,$credential)
	{
		$this->_identity = $identity;
		$this->_credential = $credential;
		$this->_remoteAuthUrl = $remoteAuthUrl;
	}
	public function authenticate()
	{
		$authResult = array(
				'code'	=> Zend_Auth_Result::FAILURE,
				'identity'	=> $this->_identity,
				'messages' => array()
			);
		
		$client = new Zend_Http_Client();
		$client->setUri($this->_remoteAuthUrl);
		$client->setParameterPost(array(
			'identity' => $this->_identity,
			'credential' => $this->_credential
		));
		
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$client->setHeaders("User-Agent:$userAgent");
		
		try {
			$response = $client->request(Zend_Http_Client::POST);
			$sResponse = $response->getBody();
			$resultIdentities = Zend_Json::decode($sResponse);
		} catch (Exception $e) {
			require_once 'Zend/Auth/Adapter/Exception.php';
			throw new Zend_Auth_Adapter_Exception($sResponse);
		}
		
		if (count($resultIdentities) < 1) { 
            $authResult['code'] = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $authResult['messages'][] = 'A record with the supplied identity could not be found.';
            return new Zend_Auth_Result($authResult['code'], $authResult['identity'], $authResult['messages']);
        } elseif (count($resultIdentities) > 1) { 
            $authResult['code'] = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            $authResult['messages'][] = 'More than one record matches the supplied identity.';
            return new Zend_Auth_Result($authResult['code'], $authResult['identity'], $authResult['messages']);
        }

        $resultIdentity = $resultIdentities[0];
		
		$obj = new Kutu_Crypt_Password();

    	if($resultIdentity['guid']=='XXISLOGINXX')
    	{
            $authResult['code'] = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            $authResult['messages'][] = 'You already login';
            return new Zend_Auth_Result($authResult['code'], $authResult['identity'], $authResult['messages']);
    	}
    	
        if(true)
        {
	        if(!$obj->matchPassword($this->_credential, $resultIdentity['password']))
	        {
	            $authResult['code'] = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
	            $authResult['messages'][] = 'Supplied credential is invalid.';
	            return new Zend_Auth_Result($authResult['code'], $authResult['identity'], $authResult['messages']);
	        }
        }

        unset($resultIdentity['zend_auth_credential_match']);
        $this->_resultRow = $resultIdentity;

        $authResult['code'] = Zend_Auth_Result::SUCCESS;
        $authResult['messages'][] = 'Authentication successful.';
        return new Zend_Auth_Result($authResult['code'], $authResult['identity'], $authResult['messages']);		
	}
}
?>