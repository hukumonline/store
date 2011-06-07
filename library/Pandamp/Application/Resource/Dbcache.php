<?php

class Pandamp_Application_Resource_Dbcache extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $options = array_change_key_case($this->getOptions(), CASE_LOWER);
        if (isset($options['enable']))
        {
            if($options['enable'])
            {
                // Get a Zend_Cache_Core object
                $cache = Zend_Cache::factory(
                    $options['frontend'],
                    $options['backend'],
                    $options['frontendoptions'],
                    $options['backendoptions']
                );
                Zend_Registry::set('cache',$cache);
                return $cache;
            }
        }
    }
}

?>