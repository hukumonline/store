<?php

class Pandamp_Application_Resource_Session extends Zend_Application_Resource_ResourceAbstract
{
    protected $_saveHandler;

    public function init()
    {
        //$cookie_timeout = 60 * 60 * 24;

        //$garbage_timeout = $cookie_timeout + 600;

		$aServerName = explode('.', $_SERVER['SERVER_NAME']);

		$count = count($aServerName);
		$domainName = '.'.$aServerName[$count-2].'.'.$aServerName[$count-1];
		
        //session_set_cookie_params($cookie_timeout, '/', $domainName);
        session_set_cookie_params(0, '/', $domainName);

        //ini_set('session.gc_maxlifetime', $garbage_timeout);

        $options = array_change_key_case($this->getOptions(), CASE_LOWER);

        if(isset($options['adapter']))
            $adapter = $options['adapter'];

        switch (strtolower($adapter))
        {
            case 'remote':
            case 'proxydb':
                $sessionHandler = new Pandamp_Session_SaveHandler_Remote();
                Zend_Session::setSaveHandler($sessionHandler);
                //$this->_saveHandler = $sessionHandler;
                //return $sessionHandler;
                break;
            default:
            case 'directdb':
                $sessionHandler = new Pandamp_Session_SaveHandler_DirectDb($options['db']['adapter'], $options['db']['params']);
                Zend_Session::setSaveHandler($sessionHandler);
                $this->_saveHandler = $sessionHandler;
                return $sessionHandler;
                break;
        }
        
    }
}
