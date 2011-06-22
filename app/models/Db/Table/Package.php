<?php

/**
 * @package kutump
 * @copyright 2008-2009 hukumonline.com/en.hukumonline.com
 * @author Nihki Prihadi <nihki@hukumonline.com>
 *
 * $Id: Package.php 2009-01-10 08:20: $DB
 */

class App_Model_Db_Table_Package extends Zend_Db_Table_Abstract 
{
	protected $_name = 'KutuUserPackage';
	
    protected function  _setupDatabaseAdapter()
    {
        $this->_db = Zend_Registry::get('db2');

        parent::_setupDatabaseAdapter();
    }
}