<?php
class Shop_Widgets_CatalogController extends Zend_Controller_Action 
{
    function detailAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $folderGuid = ($this->_getParam('folderGuid'))? $this->_getParam('folderGuid') : '';
        $start		= ($this->_getParam('start'))? $this->_getParam('start') : 0;
        $limit		= ($this->_getParam('limit'))? $this->_getParam('limit') : 0;
        
        $rowset = App_Model_Show_Catalog::show()->fetchFromFolder($folderGuid,$start,$limit);

        $content = 0;
        $data = array();
        $array_hari = array(
            1=>"Senin","Selasa","Rabu","Kamis","Jumat","Sabtu","Minggu"
        );

        $a['folderGuid'] = $folderGuid;
        $a['totalCount'] = count($rowset);

        $ii = 0;

        if ($a['totalCount']!=0)
        {
            foreach ($rowset as $row)
            {
			    $registry = Zend_Registry::getInstance();
			    $config = $registry->get(Pandamp_Keys::REGISTRY_APP_OBJECT);
			    $cdn = $config->getOption('cdn');
			    $sDir = $cdn['static']['url']['images'];
			    $smg = $cdn['static']['images'];
			    $thumb = "";
			
			    $rowsetRelatedItem = App_Model_Show_RelatedItem::show()->getDocumentById($row['guid'],'RELATED_IMAGE');
			    $itemGuid = (isset($rowsetRelatedItem['itemGuid']))? $rowsetRelatedItem['itemGuid'] : '';
			if ($itemGuid) {
			    if (Pandamp_Lib_Formater::thumb_exists($sDir ."/". $rowsetRelatedItem['relatedGuid'] . "/" . $itemGuid . ".jpg")) 	{ $thumb = $sDir ."/". $rowsetRelatedItem['relatedGuid'] . "/" . $itemGuid . ".jpg"; 	}
			    if (Pandamp_Lib_Formater::thumb_exists($sDir ."/". $rowsetRelatedItem['relatedGuid'] . "/" . $itemGuid . ".gif")) 	{ $thumb = $sDir ."/". $rowsetRelatedItem['relatedGuid'] . "/" . $itemGuid . ".gif"; 	}
			    if (Pandamp_Lib_Formater::thumb_exists($sDir ."/". $rowsetRelatedItem['relatedGuid'] . "/" . $itemGuid . ".png")) 	{ $thumb = $sDir ."/". $rowsetRelatedItem['relatedGuid'] . "/" . $itemGuid . ".png"; 	}
			}
			    if ($thumb == "") { $thumb = $sDir."/slider/image1.jpg"; }
			
			    $screenshot = "<img src=\"".$thumb."\"  vspace=\"0\" width=\"104\" border=\"0\" hspace=\"0\" align=\"left\" />";
			
            	
                $a['index'][$ii]['title'] 	= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($row['guid'],'fixedTitle');
                $a['index'][$ii]['shortTitle']	= $row['shortTitle'];
                $a['index'][$ii]['guid']	= $row['guid'];
                $a['index'][$ii]['realprice'] = $row['price'];
                $a['index'][$ii]['price'] = 'Rp '.number_format($row['price'],0,',','.');
                $a['index'][$ii]['desc']	= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($row['guid'],'fixedDescription');
	            $hari = $array_hari[date("N", strtotime($row['publishedDate']))];
                $a['index'][$ii]['publish']	= $hari . ', '. date("d F Y",strtotime($row['publishedDate']));
                $a['index'][$ii]['images']	= '<div class="width1 column first ta-center">'.$screenshot.'</div>';
                $ii++;
            }
        }
        if ($a['totalCount'] == 0)
        {
            $a['index'][0]['title'] = "-";
            $a['index'][0]['shortTitle'] = "-";
            $a['index'][0]['guid'] = "-";
            $a['index'][0]['publish'] = "-";
            $a['index'][0]['desc'] = "-";
        }
        echo Zend_Json::encode($a);
    }
}