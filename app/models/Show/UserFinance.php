<?php

/**
 * Description of UserFinance
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Show_UserFinance extends App_Model_Db_DefaultAdapter
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

    public function getUserFinance($where)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT KUF.*, KU.fullName AS FN, KU.username AS UN, KU.createdDate, KU.createdBy, KU.modifiedDate, KU.modifiedBy FROM KutuUserFinance AS KUF, hid.KutuUser AS KU WHERE userId = '$where' AND KU.kopel = KUF.userId ");

        $result = $query->fetchAll(Zend_Db::FETCH_ASSOC);

        return $result;
    }
}
