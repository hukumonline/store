<?php

class Dms_CatalogController extends Zend_Controller_Action 
{
	function terbaruAction()
	{
        $rowset = App_Model_Show_Catalog::show()->fetchFromFolder('fb16',0,5);

        $content = 0;
        $data = array();

        foreach ($rowset as $row)
        {
            $data[$content][0] = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($row['guid'],'fixedTitle');
            $data[$content][1] = $row['shortTitle'];
            $data[$content][2] = date("d/m/y",strtotime($row['createdDate']));
            $data[$content][3] = $row['guid'];
            $data[$content][4] = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($row['guid'],'fixedDescription');
            $content++;
        }

        $num_rows = count($rowset);

        $this->view->numberOfRows = $num_rows;
        $this->view->data = $data;
	}
}