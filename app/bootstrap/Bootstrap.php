<?php

/**
 * General Bootstrapping class
 * @author Nihki Prihadi <nihki@madaniyah.com>
 *
 */

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap 
{
    /**
     * @var Zend_Log
     */
    protected $_logger;

    /**
     * @var Zend_Application_Module_Autoloader
     */
    protected $_resourceLoader;

    /**
     * @var Zend_Controller_Front
     */
    public $frontController;

    /**
     * @var Configure the pluginLoader Cache
     */
    protected function _initPluginLoaderCache()
    {
        if ('production' == $this->getEnvironment()) {
            $classFileIncCache = APPLICATION_PATH . '/../data/cache/pluginLoaderCache.php';
            if (file_exists($classFileIncCache))
            {
                include_once $classFileIncCache;
            }
            Zend_Loader_PluginLoader::setIncludeFileCache($classFileIncCache);
        }
    }

    protected function _initDbRegistry()
    {
        $multidb = $this->getPluginResource('multidb');
        $multidb->init();

        Zend_Registry::set('db1', $multidb->getDb('db1'));
        Zend_Registry::set('db2', $multidb->getDb('db2'));
    }

    /**
     * Setup the logging
     */
    protected function _initLogging()
    {
        $this->bootstrap('frontController');
        $logger = new Zend_Log();

        $writer = 'production' == $this->getEnvironment() ?
                new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/log/app.log') :
                New Zend_Log_Writer_Firebug();
        $logger->addWriter($writer);

        if ('production' == $this->getEnvironment()) {
            $filter = new Zend_Log_Filter_Priority(Zend_Log::CRIT);
            $logger->addFilter($filter);
        }

        $this->_logger = $logger;
        Zend_Registry::set('log', $logger);
    }

    /**
     *
     * @return Zend_Application_Module_Autoloader
     * Configure the default module autoloading
     */
    protected function _initDefaultModuleAutoloader()
    {
        $this->_logger->info('Bootstrap ' . __METHOD__);
        
        $this->_resourceLoader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'App',
            'basePath' => APPLICATION_PATH));

    }

    /**
     * Setup the database profiling
     */
    protected function _initDbProfiler()
    {
        $this->_logger->info('Bootstrap ' . __METHOD__);

        if ('production' !== $this->getEnvironment()) {
            //$this->bootstrap('db');
            $this->bootstrap('multidb');
            $profiler = new Zend_Db_Profiler_Firebug('All Db Queries');
            $profiler->setEnabled(true);
            $database = Zend_Registry::get('db1');
            //$this->getPluginResource('db')->getDbAdapter()->setProfiler($profiler);
            $database->setProfiler($profiler);
        }
    }

    /**
     * Add Controller Action Helpers
     */
    protected function _initActionHelpers()
    {
        $this->_logger->info('Bootstrap ' . __METHOD__);

        /*
         * We want to set the encoding to UTF-8, so we won't rely on the ViewRenderer action helper by default,
         * but will construct view object and deliver it to the ViewRenderer after setting some options.
         */
        $view = new Zend_View(array('encoding'=>'UTF-8'));
        $view->addHelperPath(ROOT_DIR.'/library/Pandamp/Controller/Action/Helper','Pandamp_Controller_Action_Helper');
        $viewRendered = new Zend_Controller_Action_Helper_ViewRenderer($view);
        Zend_Controller_Action_HelperBroker::addHelper($viewRendered);
        $authUsersHelper = new Pandamp_Controller_Action_Helper_RegistryAccess();
		Zend_Controller_Action_HelperBroker::addHelper($authUsersHelper);
    }

    /**
     * Init the Db Metadata and Paginator Caches
     */
    protected function _initDbCaches()
    {
        $this->_logger->info('Bootstrap ' . __METHOD__);

        if ('production' == $this->getEnvironment()) {
            // Metadata cache for Zend_Db_Table
            $frontendOptions = array(
                'automatic_serialization' => true
            );

            $cache = Zend_Cache::factory('Core',
                'Apc',
                $frontendOptions
            );
            Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        }
    }

    /*
     *
    protected function _initBrowserCachePlugin()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Pandamp_Controller_Plugin_BrowserCache(), 101);
    }
     */

    protected function _initZIDS() {
        // Ensure the front controller is initialized
        $this->bootstrap('FrontController');

        // Retrieve the front controller from the bootstrap registry
        $front = $this->getResource('FrontController');

        // Only enable zfdebug if options have been specified for it
        if ($this->hasOption('zids'))
        {
            // Create ZIDS instance
            $zids = new Pandamp_Controller_Plugin_Ids($this->getOption('zids'));

            // create a logger (ADOPT THIS TO YOUR NEEDS!)
            $logger = new Zend_Log ();
            $filter = new Zend_Log_Filter_Priority(Zend_Log::ERR);
            $writer = new Zend_Log_Writer_Stream (APPLICATION_PATH . "/../data/log/log.txt");
            $logger->addWriter ( $writer );

            // register all plugins that you need
            $zids->registerPlugin(new Pandamp_Controller_Plugin_ActionPlugin_Ignore());
            $zids->registerPlugin(new Pandamp_Controller_Plugin_ActionPlugin_Email());
            $zids->registerPlugin(new Pandamp_Controller_Plugin_ActionPlugin_Log($logger));
            $zids->registerPlugin(new Pandamp_Controller_Plugin_ActionPlugin_Redirect());

            // Register ZIDS with the front controller
            $front->registerPlugin($zids);
        }
    }
    
    /**
     * Initialize our view and add it to the ViewRenderer action helper.
     */
    protected function _initView()
    {
        // Initialize view
        $view = new Zend_View();

        // Add it to the ViewRenderer
        $viewRenderer =
            Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        // Return it, so that it can be stored by the bootstrap
        return $view;
    }

    /**
     * Here we will initialize any view helpers.    This will also setup basic
     * head information for the view/layout.
     */
    protected function _initViewHelpers()
    {
        $this->bootstrap(array('frontcontroller', 'view'));
        $frontController = $this->getResource('frontcontroller');
        $view = $this->getResource('view');

        // Add helper paths.
        $view->addHelperPath(APPLICATION_PATH . '/../library/Pandamp/Controller/Action/Helper', 'Pandamp_Controller_Action_Helper');

        // Set our DOCTYPE
        $view->doctype('XHTML1_STRICT');

            
    }

}
