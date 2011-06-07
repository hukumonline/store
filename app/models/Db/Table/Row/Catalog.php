<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Catalog
 *
 * @author user
 */
class App_Model_Db_Table_Row_Catalog extends Zend_Db_Table_Row_Abstract
{
    protected function _insert()
    {
        if(empty($this->guid))
        {
            $guidMan = new Pandamp_Core_Guid();
            $this->guid = $guidMan->generateGuid();
        }

        if(!empty($this->shortTitle))
        {
            $sTitleLower = strtolower($this->shortTitle);
            $sTitleLower = preg_replace("/[^a-zA-Z0-9 s]/", "", $sTitleLower);
            $sTitleLower = str_replace(' ', '-', $sTitleLower);
            $this->shortTitle = $sTitleLower;
        }

        $today = date('Y-m-d H:i:s');

        if(empty($this->createdDate))
            $this->createdDate = $today;
        if(empty($this->modifiedDate) || $this->modifiedDate=='0000-00-00 00:00:00')
            $this->modifiedDate = $today;

        $this->deletedDate = '0000-00-00 00:00:00';

        if(empty($this->createdBy))
        {
            $auth = Zend_Auth::getInstance();
            if($auth->hasIdentity())
            {
                $this->createdBy = $auth->getIdentity()->username;
            }
        }

        if(empty($this->modifiedBy))
            $this->modifiedBy = $this->createdBy;
        if(empty($this->status))
            $this->status = 0;

    }
    public function findDependentRowsetCatalogAttribute()
    {
        return $this->findDependentRowset('App_Model_Db_Table_CatalogAttribute');
    }
}
