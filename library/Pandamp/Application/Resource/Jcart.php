<?php
class Pandamp_Application_Resource_Jcart extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        require_once(ROOT_DIR.'/js/jcart/jcart.php');

        $options = array_change_key_case($this->getOptions(), CASE_LOWER);

        return $options;
    }
}