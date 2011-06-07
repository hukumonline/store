<?php
define('ROOT_DIR',dirname(__FILE__));
define('ROOT_PATH',dirname(__FILE__));
define('LIB_PATH' , ROOT_PATH . '/library') ;
define('APPLICATION_PATH', ROOT_PATH . '/app');
define('MODULE_PATH' , ROOT_PATH . '/app/modules') ;

// define the path for configuration file
define('CONFIG_PATH' , ROOT_PATH . '/app/configs') ;
 
/* ------------------------------------------
 * Config for calender
 */

define("WEEK_START", 0);
define("TIME_DISPLAY_FORMAT", "12hr");
define("CURR_TIME_OFFSET", 0);
define("MAX_TITLES_DISPLAYED", 5);
define("TITLE_CHAR_LIMIT", 37);

/* ------------------------------------------ */

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV',
              (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV')
                                         : 'production'));

// Include path
set_include_path(LIB_PATH . PATH_SEPARATOR . ROOT_PATH.'/library/Phpids/lib' . PATH_SEPARATOR . get_include_path());

require_once('Pandamp/Core/Util.php');
$pUtil = new Pandamp_Core_Util();
define('ROOT_URL', $pUtil->getRootUrl(ROOT_DIR));

/** Zend_Application */
define('ZEND_APPLICATION_REGISTER', 'application');
define('APPLICATION_CONFIG_FILENAME', 'zhol.ini');

// Zend_Application
require_once 'Pandamp/ZP.php';

$application = new Pandamp_ZP(
    APPLICATION_ENV,
        array(
            'configFile' => CONFIG_PATH . '/'.APPLICATION_CONFIG_FILENAME
        )
);

$registry = Zend_Registry::getInstance();
$registry->set(Pandamp_Keys::REGISTRY_APP_OBJECT, $application);

$registry->set('files', $_FILES);

$regconfig = new Zend_Config_Ini(CONFIG_PATH.'/zhol.ini', 'general'); 
$registry->set('config', $regconfig); 

define('JCART_PATH_URL',ROOT_URL."/js/jcart/");
define('JCART_FORMACTION_URL',ROOT_URL."/checkout/cart");

