<?php

/**
 * Description of Application
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Pandamp_Application
{
    static public function getResource($resourceName, $moduleName='')
    {
        $front = Zend_Controller_Front::getInstance();
        $boot = $front->getParam('bootstrap');

        if(empty($boot))
        {
            $application = new Zend_Application(APPLICATION_ENV,CONFIG_PATH . '/' . APPLICATION_CONFIG_FILENAME);
            if(empty($moduleName))
            {
                $application->getBootstrap()->bootstrap($resourceName);
                return $application->getBootstrap()->getResource($resourceName);
            }
            else
            {
                $application->getBootstrap()->bootstrap('modules');
                return $application->getBootstrap()->getResource('modules')->offsetGet($moduleName)->getResource($resourceName);
            }
        }
        else
        {
            if(empty($moduleName))
            {
                return $front->getParam('bootstrap')->getResource($resourceName);
            }
            else
            {
                return $front->getParam('bootstrap')->getResource('modules')->offsetGet($moduleName)->getResource($resourceName);
            }
        }
    }
    static public function getOption($key)
    {
        $front = Zend_Controller_Front::getInstance();
        $boot = $front->getParam('bootstrap');

        if(empty($boot))
        {
            $application = new Zend_Application(APPLICATION_ENV,CONFIG_PATH . '/' . APPLICATION_CONFIG_FILENAME);
            return $application->getOption($key);
        }
        else
        {
            return $front->getParam('bootstrap')->getOption($key);
        }
    }
}
?>
