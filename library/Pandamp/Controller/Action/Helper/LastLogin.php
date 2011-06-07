<?php

/**
 * Description of LastLogin
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Pandamp_Controller_Action_Helper_LastLogin
{
    public function lastLogin($userId=NULL)
    {
        if ($userId)
        {
            $id = $userId;
        }
        else
        {
            $auth = Zend_Auth::getInstance();
            if (!$auth->hasIdentity())
            {
                return;
            }

            $id = $auth->getIdentity()->kopel;

        }
		
		$tblUserAccessLog = new App_Model_Db_Table_UserLog();
		$rowUserAccessLog = $tblUserAccessLog->fetchRow("user_id='".$id."' AND NOT (lastlogin='0000-00-00 00:00:00' or isnull(lastlogin))",'user_access_log_id DESC');
		
			
		if (isset($rowUserAccessLog)) 
		{
	        $array_hari = array(1=>"Senin","Selasa","Rabu","Kamis","Jumat","Sabtu","Minggu");
	        $hari = $array_hari[date("N",strtotime($rowUserAccessLog->lastlogin))];


			$dLog = $hari . ', '.date('j F Y \j\a\m H:i',strtotime($rowUserAccessLog->lastlogin)). ' <br>dari '.$rowUserAccessLog->user_ip;
		} else {
			$dLog = '-';
		}
			
		return $dLog;			
    }

}
