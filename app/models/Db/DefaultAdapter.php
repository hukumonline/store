<?php

/**
 * Description of DefaultAdapter
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Db_DefaultAdapter
{
    protected function _getDefaultAdapter()
    {
        $defaultAdapter = Zend_Db_Table::getDefaultAdapter();
        return $defaultAdapter;
    }
    protected function _dbSelect()
    {
        $dbSelect = new Zend_Db_Select($this->_getDefaultAdapter());
        return $dbSelect;
    }
}
