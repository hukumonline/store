<?php

class Pandamp_Controller_Action_Helper_Cdn extends Zend_View_Helper_Abstract 
{
    static $_types = array(
        'default' => '',
        'images'  => 'http://static.hukumonline.n1/frontend/default/images',                
        'styles'  => 'http://static.hukumonline.n1/frontend/default/css',
        'scripts' => 'http://js.hukumonline.n1',
        'rim' 	  => 'http://images.hukumonline.n1'
    );
    
    static function setTypes($types)        
    {
		self::$_types = $types;
    }
    
    public function cdn($type = 'default')        
    {
        if (!isset(self::$_types[$type])) {
			throw new Exception('No CDN set for resource type ' . $type);
        }
        return self::$_types[$type];
    }
	
}