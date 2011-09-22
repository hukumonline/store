<?php

/**
 * Description of Order
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Db_Table_Row_Order extends Zend_Db_Table_Row_Abstract
{
    protected function  _postDelete()
    {
        $tblOrderDetail = new App_Model_Db_Table_OrderDetail();
        $tblOrderDetail->delete("orderId=".$this->orderId);

        $tblOrderHistory = new App_Model_Db_Table_OrderHistory();
        $tblOrderHistory->delete("orderId=".$this->orderId);

        $tblPaymentHistory = new App_Model_Db_Table_PaymentConfirmation();
        $tblPaymentHistory->delete("orderId=".$this->orderId);

        $tblNsiapay = new App_Model_Db_Table_Nsiapay();
        $tblNsiapay->delete("orderId=".$this->orderId);

        $tblNsiapayHistory = new App_Model_Db_Table_NsiapayHistory();
        $tblNsiapayHistory->delete("orderId='$this->invoiceNumber'");
    }
}
