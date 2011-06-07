<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CatalogAttribute
 *
 * @author user
 */
class App_Model_Db_Table_Rowset_CatalogAttribute extends Zend_Db_Table_Rowset_Abstract
{
    function findByAttributeGuid($attributeGuid)
    {
        foreach ($this as $row) {
            if ($row->attributeGuid == $attributeGuid)
            {
                return $row;
            }
        }
        return null;
    }
}
