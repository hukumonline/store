<?php
class Shop_BrowseController extends Zend_Controller_Action 
{
	function viewcartAction()
	{
		
	}
	function testimonialAction()
	{
		
	}
	function viewAction()
	{
		
	}
	function indexcheckoutAction()
	{
        $folderGuid = ($this->_getParam('folderGuid'))? $this->_getParam('folderGuid') : 'lt4dbe28bd4b956';
        
        $data = array();
        
        $rowset = $rowset = App_Model_Show_Catalog::show()->fetchFromFolder('lt4dbe28bd4b956');

        $num_rows = count($rowset);
        $limit = 10;

        $data['folderGuid'] = $folderGuid;
        $data['totalCount'] = $num_rows;
        $data['totalCountRows'] = $num_rows;
        $data['limit'] = $limit;

        $this->view->aData = $data;
	}
}