<?php

/**
 * Description of Order
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Show_Order extends App_Model_Db_DefaultAdapter
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

    public function getOrderSummary($userId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT KO.*,KOS.ordersStatus,
                                COUNT(itemId) AS countTotal,KU.*
                                FROM
                                ((KutuOrder AS KO
                                LEFT JOIN KutuOrderDetail AS KOD
                                    ON KOD.orderId=KO.orderId)
                                LEFT JOIN hid.KutuUser AS KU
                                    ON KU.kopel = KO.userId)
                                LEFT JOIN KutuOrderStatus AS KOS
                                    ON KOS.orderStatusId = KO.orderStatus
                                WHERE KO.userId = $userId
                                GROUP BY(KO.orderId) DESC");

        $result = $query->fetchAll(Zend_Db::FETCH_ASSOC);

        return $result;
    }
    function countOrders($userId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("Select count(KO.orderId) AS count From KutuOrder as KO, KutuOrderDetail AS KOD
    	where KOD.orderID =KO.orderID AND KO.userId=$userId");
        $result = $query->fetchAll(Zend_Db::FETCH_OBJ);

        return $result[0]->count;
    }
    public function getTransactionToConfirm($userId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT
                                    KO.*,KOS.ordersStatus,
                                    COUNT(itemId) AS countTotal,KU.kopel
                                FROM
                                    ((KutuOrder AS KO
                                LEFT JOIN KutuOrderDetail AS KOD
                                    ON KOD.orderId = KO.orderId)
                                LEFT JOIN hid.KutuUser AS KU
                                    ON KU.kopel = KO.userId)
                                LEFT JOIN KutuOrderStatus AS KOS
                                    ON KOS.orderStatusId = KO.orderStatus
                                WHERE
                                    KO.userId = '$userId'
                                AND
                                    (paymentMethod = 'bank'
                                AND
                                    (
                                    orderStatus = 5
                                    OR orderStatus = 1
                                    OR orderStatus = 4
                                    OR orderStatus = 6
                                    ))
                                GROUP BY(KO.orderId) ASC");

        $result = $query->fetchAll(Zend_Db::FETCH_ASSOC);

        return $result;
    }
    public function getTransactionToConfirmCount($userId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT
                                    COUNT(orderId) AS countConfirm
                                FROM
                                    KutuOrder
                                WHERE
                                    userId = '$userId'
                                AND
                                    (
                                    paymentMethod = 'bank'
                                AND
                                    (
                                    orderStatus = 5
                                    OR orderStatus = 1
                                    OR orderStatus = 4
                                    OR orderStatus = 6
                                    ))");
        $result = $query->fetchAll(Zend_Db::FETCH_OBJ);

        return $result[0]->countConfirm;
    }
    public function getOrderAndStatus($orderId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT
                            KO.*, KOS.*
                            FROM
                                KutuOrder AS KO,
                                KutuOrderStatus AS KOS
                            WHERE
                                orderStatus =orderStatusId
                            AND
                                orderId = $orderId");

        $result = $query->fetchAll(Zend_Db::FETCH_ASSOC);

        return $result;
    }
    public function getOrder($idOrder)
    {
        $db = parent::_dbSelect();
        $select = $db->from('KutuOrder')
                    ->where("orderId = ". $idOrder);

        $result = parent::_getDefaultAdapter()->fetchAll($select);

        return $result;
    }
    public function getOrderDetail($orderId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT KO.*, KOD.*
                                FROM KutuOrder AS KO
                                JOIN KutuOrderDetail AS KOD
                                ON KOD.orderId = KO.orderId
                                WHERE KO.orderId = $orderId");

        $result = $query->fetchAll(Zend_Db::FETCH_ASSOC);

        return $result;
    }
    public function outstandingUserAmout($userId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT SUM(orderTotal) AS total FROM KutuOrder where userId = '$userId' AND  orderStatus=5");

        $result = $query->fetchAll(Zend_Db::FETCH_OBJ);

        return $result[0]->total;
    }
    public function getDocumentSummary($userId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT KOD.*, KO.datePurchased AS purchasingDate
                                FROM
                                KutuOrderDetail AS KOD,
                                KutuOrder AS KO
                                WHERE
                                    KO.orderId = KOD.orderId
                                AND
                                    KO.userId = '$userId'
                                AND 
                                	KO.paymentMethodNote != 'membership'
                                AND
                                    (KO.orderStatus = 3
                                    OR
                                    KO.orderStatus = 5)");

        $result = $query->fetchAll(Zend_Db::FETCH_ASSOC);

        return $result;
    }
    public function countDocument($userId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT count(itemId) as totalDoc
                                FROM
                                    KutuOrderDetail AS KOD,
                                    KutuOrder AS KO
                                WHERE
                                    KO.orderId = KOD.orderId
                                AND
                                    KO.userId = '$userId'
                                AND
                                    (KO.orderStatus = 3
                                    OR
                                    KO.orderStatus = 5)");

        $result = $query->fetchAll(Zend_Db::FETCH_OBJ);

        return $result[0]->totalDoc;
    }
    public function getAmount($orderId)
    {
        $db = parent::_getDefaultAdapter();
        $query = $db->query("SELECT
                                orderTotal AS mount
                                FROM KutuOrder
                                WHERE
                                orderId = $orderId");

        $result = $query->fetchAll(Zend_Db::FETCH_OBJ);

        return $result[0]->mount;
    }
}
