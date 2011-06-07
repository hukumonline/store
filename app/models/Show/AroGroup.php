<?php

/**
 * Description of AroGroup
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Show_AroGroup extends App_Model_Db_DefaultAdapter
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
    public function getUserGroup($packageId)
    {
        $db = parent::_dbSelect();
        $select = $db->from('gacl_aro_groups','*','hid')
                    ->where("id = $packageId");

        $result = parent::_getDefaultAdapter()->fetchRow($select);

        return $result;
    }
}
