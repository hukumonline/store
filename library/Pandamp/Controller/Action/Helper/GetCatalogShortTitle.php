<?php

class Pandamp_Controller_Action_Helper_GetCatalogShortTitle
{
    public function getCatalogShortTitle($guid)
    {
        $rowset = App_Model_Show_Catalog::show()->getCatalogByGuid($guid);

        if ($rowset)
            return $rowset['shortTitle'];
        else
            return 'no-title';
    }
}
