<?php
class Pandamp_Controller_Action_Helper_GetGroupName
{
	public function getGroupName($id)
    {
    	$modelGroup = new App_Model_Db_Table_Group();
    	$row = $modelGroup->fetchRow("id=$id");
    	return ($row) ? $row->name : '-';
   	}
}
?>