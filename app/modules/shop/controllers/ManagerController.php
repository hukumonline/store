<?php

class Shop_ManagerController extends Zend_Controller_Action 
{
	protected $_user;
	
	function preDispatch()
	{
		$this->_helper->layout->setLayout('layout-store');
        Zend_Session::start();

        $sReturn = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        $sReturn = base64_encode($sReturn);

        $auth = Zend_Auth::getInstance();
		if (!$auth->hasIdentity())
		{
			$identity = Pandamp_Application::getResource('identity');
			$loginUrl = $identity->loginUrl;

			$this->_redirect($loginUrl.'?returnTo='.$sReturn);     
		}
		else 
		{
			$this->_user = $auth->getIdentity();			
		}
	}
    public function errorAction()
    {
        $view = $this->_request->getParam('view');

        switch (strtolower($view))
        {
            case 'orderalreadypaid':
                $this->renderScript('manager/error-orderalreadypaid.phtml');
                break;
            case 'noorderfound':
                $this->renderScript('manager/error-noorderfound.phtml');
                break;
            case 'notowner':
            default:
                $this->renderScript('manager/error-notowner.phtml');
                break;
        }
    }
}