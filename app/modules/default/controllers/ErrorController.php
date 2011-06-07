<?php

class ErrorController extends Zend_Controller_Action
{
    private $_notifier;
    private $_error;
    private $_environment;

    public function init()
    {
        parent::init();

        $this->_helper->layout->disableLayout();

        $bootstrap = $this->getInvokeArg('bootstrap');

        $environment = $bootstrap->getEnvironment();
        $error = $this->_getParam('error_handler');
        $mailer = new Zend_Mail();
        $session = new Zend_Session_Namespace();
//        $database = $bootstrap->getResource('db');
//        $profiler = $database->getProfiler();
        $database = Zend_Registry::get('db1');
        $profiler = $database->getProfiler();

        $this->_notifier = new Application_Service_Notifier_Error(
            $environment,
            $error,
            $mailer,
            $session,
            $profiler,
            $_SERVER
        );

        $this->_error = $error;
        $this->_environment = $environment;
   }

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Page not found';
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                //$this->view->message = 'Application error';
                $this->_applicationError();

                break;
        }

        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->crit($this->view->message, $errors->exception);
        }

        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }

        $this->view->request   = $errors->request;
    }

    private function _applicationError()
    {
        $fullMessage = $this->_notifier->getFullErrorMessage();
        $shortMessage = $this->_notifier->getShortErrorMessage();

        switch ($this->_environment) {
            case 'live':
                $this->view->message = $shortMessage;
                break;
            case 'test':
                $this->_helper->layout->setLayout('blank');
                $this->_helper->viewRenderer->setNoRender();

                $this->getResponse()->appendBody($shortMessage);
                break;
            default:
                $this->view->message = nl2br($fullMessage);
        }

        $this->_notifier->notify();
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasPluginResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }


}

class Application_Service_Notifier_Error
{
    protected $_environment;
    protected $_mailer;
    protected $_session;
    protected $_error;
    protected $_profiler;

    public function __construct(
        $environment,
        ArrayObject $error,
        Zend_Mail $mailer,
        Zend_Session_Namespace $session,
        Zend_Db_Profiler $profiler,
        Array $server)
    {
        $this->_environment = $environment;
        $this->_mailer = $mailer;
        $this->_error = $error;
        $this->_session = $session;
        $this->_profiler = $profiler;
        $this->_server = $server;
    }

    public function getFullErrorMessage()
    {
        $message = '';

        if (!empty($this->_server['SERVER_ADDR'])) {
            $message .= "Server IP: " . $this->_server['SERVER_ADDR'] . "\n";
        }

        if (!empty($this->_server['HTTP_USER_AGENT'])) {
            $message .= "User agent: " . $this->_server['HTTP_USER_AGENT'] . "\n";
        }

        if (!empty($this->_server['HTTP_X_REQUESTED_WITH'])) {
            $message .= "Request type: " . $this->_server['HTTP_X_REQUESTED_WITH'] . "\n";
        }

        $message .= "Server time: " . date("Y-m-d H:i:s") . "\n";
        $message .= "RequestURI: " . $this->_error->request->getRequestUri() . "\n";

        if (!empty($this->_server['HTTP_REFERER'])) {
            $message .= "Referer: " . $this->_server['HTTP_REFERER'] . "\n";
        }

        $message .= "Message: " . $this->_error->exception->getMessage() . "\n\n";
        $message .= "Trace:\n" . $this->_error->exception->getTraceAsString() . "\n\n";
        $message .= "Request data: " . var_export($this->_error->request->getParams(), true) . "\n\n";

        $it = $this->_session->getIterator();

        $message .= "Session data:\n\n";
        foreach ($it as $key => $value) {
            $message .= $key . ": " . var_export($value, true) . "\n";
        }
        $message .= "\n";

        $query = $this->_profiler->getLastQueryProfile();
        if ($query) {
            $query = $query->getQuery();
            $queryParams = $this->_profiler->getLastQueryProfile()->getQueryParams();
            $message .= "Last database query: " . $query . "\n\n";
            $message .= "Last database query params: " . var_export($queryParams, true) . "\n\n";
        }

        return $message;
    }

    public function getShortErrorMessage()
    {
        $message = '';

        switch ($this->_environment) {
            case 'live':
                $message .= "It seems you have just encountered an unknown issue.";
                $message .= "Our team has been notified and will deal with the problem as soon as possible.";
                break;
            default:
                $message .= "Message: " . $this->_error->exception->getMessage() . "\n\n";
                $message .= "Trace:\n" . $this->_error->exception->getTraceAsString() . "\n\n";
        }

        return $message;
    }

    public function notify()
    {
        if (!in_array($this->_environment, array('live', 'stage', 'production'))) {
            return false;
        }
        
        $this->_mailer->setFrom('do-not-reply@hukumonline.com');
        $this->_mailer->setSubject("Exception on Application");
        $this->_mailer->setBodyText($this->getFullErrorMessage());
        $this->_mailer->addTo('nihki@hukumonline.com');

        $mailTransport = Pandamp_Application::getResource('mail');
        return $this->_mailer->send($mailTransport);
    }
}