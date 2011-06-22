<?php
class App_Model_Db_Table_Group extends Zend_Db_Table_Abstract  
{ 
    protected $_name = 'gacl_aro_groups';
    
    protected function  _setupDatabaseAdapter()
    {
        $this->_db = Zend_Registry::get('db2');

        parent::_setupDatabaseAdapter();
    }
}