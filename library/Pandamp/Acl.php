<?php

/**
 *
 * @package 	Anoa
 * @author      Nihki Prihadi <nihki@madaniyah.com>
 * @version     1.1
 * @since		2009-09-28 19:52:12Z
 * @uses 		Access Control List (ACL)
 * @abstract
 *
 */

class Pandamp_Acl
{
    function manager()
    {
        $registry = Zend_Registry::getInstance();
        $application = Zend_Registry::get(Pandamp_Keys::REGISTRY_APP_OBJECT);
        $application->getBootstrap()->bootstrap('acl');
        return $application->getBootstrap()->getResource('acl');
    }
}
