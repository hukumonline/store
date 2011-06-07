<?php

/**
 * Description of UserLog
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Db_Table_UserLog extends Zend_Db_Table_Abstract
{
    protected $_name = 'KutuUserAccessLog';
    protected $_schema = 'hid';
    protected $_referenceMap = array(
        'User' => array(
            'columns'       => array('user_id'),
            'refTableClass' => array('App_Model_Db_Table_User'),
            'refColumns'    => array('kopel')
        )
    );
}
