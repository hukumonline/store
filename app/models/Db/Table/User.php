<?php

/**
 * Description of User
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Db_Table_User extends Zend_Db_Table_Abstract
{
    protected $_name = 'KutuUser';
    
    protected function  _setupDatabaseAdapter()
    {
        $this->_db = Zend_Registry::get('db2');

        parent::_setupDatabaseAdapter();
    }
    public function getUserCount($guid)
    {
        if ($guid == "") {
            return 0;
        }

        $select = $this->select()
                  ->from($this, array(
                    'COUNT(kopel) as count_id'
                  ))
                  ->where('kopel = ?', $guid);

        $row = $this->fetchRow($select);

        return ($row !== null) ? $row->count_id : 0;
    }
}
