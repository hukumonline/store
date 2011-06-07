<?php

/**
 *
 * @package 	Anoa
 * @author      Nihki Prihadi <nihki@madaniyah.com>
 * @version     1.1
 * @since		2009-09-28 19:23:33Z
 * @uses 		Access Control List (ACL)
 * @abstract
 *
 */

class Pandamp_Application_Resource_Acl extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $options = array_change_key_case($this->getOptions(), CASE_LOWER);

        switch (strtolower($options['adapter']))
        {
            case 'zendacl':
                $db = Zend_Db::factory($options['db']['adapter'], $options['db']['params']);
                $aclAdapter = new Pandamp_Acl_Adapter_zendAcl($db);
                return $aclAdapter;
            case 'phpgacl':
                $aclAdapter = new Pandamp_Acl_Adapter_PhpGacl($options['db']['adapter'], $options['db']['params']);
                return $aclAdapter;
            default :
                throw new Zend_Exception('Pandamp_Acl does not support adapter: '. $config->acl->adapter. '. Please check your configuration.', 101);
        }

    }
}
