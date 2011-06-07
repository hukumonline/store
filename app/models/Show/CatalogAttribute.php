<?php

/**
 * Description of CatalogAttribute
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Show_CatalogAttribute extends App_Model_Db_DefaultAdapter
{
    /**
     * class instance object
     */
    private static $_instance;

    /**
     * de-activate constructor
     */
    final private function  __construct() {}

     /**
      * de-activate object cloning
      */
    final private function  __clone() {}

    /**
     * @return obj
     */
    public function show()
    {
        if (!isset(self::$_instance)) {
                $show = __CLASS__;
                self::$_instance = new $show;
        }
        return self::$_instance;
    }
    
    public function getCatalogAttributeValue($catalogGuid, $attributeGuid)
    {
        $db = parent::_dbSelect();
        $select = $db->from('KutuCatalogAttribute',array(
                            'value'
                      ))
                      ->where('catalogGuid=?',$catalogGuid)
                      ->where('attributeGuid=?',$attributeGuid);

        $row = parent::_getDefaultAdapter()->fetchRow($select);

        return ($row !== null) ? $row['value'] : '';
    }


}
