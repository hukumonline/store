<?php

/**
 * Description of RelatedItem
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Show_RelatedItem extends App_Model_Db_DefaultAdapter
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

    /**
     * @return obj
     */
    public function getDocumentById($catalogGuid, $relateAs)
    {
        $db = parent::_dbSelect();
        $statement = $db->from('KutuRelatedItem')->where('relatedGuid=?', $catalogGuid)->where('relateAs=?', $relateAs);
        $row = parent::_getDefaultAdapter()->fetchRow($statement);

        return $row;
    }
}
