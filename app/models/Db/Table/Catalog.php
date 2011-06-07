<?php

/**
 * Description of Catalog
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Db_Table_Catalog extends Zend_Db_Table_Abstract
{
    protected $_name = 'KutuCatalog';
    protected $_rowClass = 'App_Model_Db_Table_Row_Catalog';
    protected $_rowsetClass = 'App_Model_Db_Table_Rowset_CatalogAttribute';
    protected $_dependentTables = array('App_Model_Db_Table_CatalogAttribute','App_Model_Db_Table_CatalogFolder');
}
?>
