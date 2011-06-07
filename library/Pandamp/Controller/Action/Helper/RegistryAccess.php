<?php
class Pandamp_Controller_Action_Helper_RegistryAccess extends Zend_Controller_Action_Helper_Abstract
{
    public function preDispatch()
    {
        $actionController = $this->getActionController();
        $actionController->view->isLoggedIn = $this->isLoggedIn();
        $actionController->view->username = $this->getUserName();
        $actionController->view->getUserId = $this->getUserId();
    }
	public function isLoggedIn()
	{
		$auth = Zend_Auth::getInstance();
		if (!$auth->hasIdentity()) {
			return null;
		}
		return $auth->hasIdentity();
	}
	public function getUserName()
	{
        if (!$this->isLoggedIn())
        {
            return null;            
        }
        
		$auth = Zend_Auth::getInstance();
        $identity = $auth->getIdentity()->username;
        return $identity;
	}
    public function getUserId()
    {
    	if (!$this->isLoggedIn())
    	{
    		return 0;
    	}
    	
    	$auth = Zend_Auth::getInstance();
		return $auth->getIdentity()->kopel;
    }

	public function logoutUrl()
	{
		// check authentication with zend
		$auth = Zend_Registry::get(Pandamp_Keys::REGISTRY_AUTH_OBJECT);
		if ($auth->hasIdentity()) {
			$logUrl = "<a href='./logout'>Logout</a>";
		}
		
		return $logUrl;
	}
}
?>