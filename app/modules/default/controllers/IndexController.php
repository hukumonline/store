<?php
class IndexController extends Zend_Controller_Action 
{
	function preDispatch()
	{
	}
	function indexAction()
	{
		
	}
	function headerAction()
	{
		
	}
	function subheaderAction()
	{
        $sReturn = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        $sReturn = base64_encode($sReturn);

		$identity = Pandamp_Application::getResource('identity');
		$loginUrl = $identity->loginUrl;
		$logoutUrl = $identity->logoutUrl;
		$signUp = $identity->signUp;
		$profile = $identity->profile;
		
		$this->view->loginUrl = $loginUrl.'?returnTo='.$sReturn;
		$this->view->logoutUrl = $logoutUrl.'/'.$sReturn;
		$this->view->signUp = $signUp;
		$this->view->profile = $profile;
		
	}
	function footerAction()
	{
		
	}
}