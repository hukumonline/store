<?php

/**
 * Description of Catalog
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Pandamp_Core_Hol_Catalog
{
    public function save($aData)
    {
        if(empty($aData['profileGuid']))
                throw new Zend_Exception('Catalog Profile can not be EMPTY!');

        $tblCatalog = new App_Model_Db_Table_Catalog();

        $gman = new Pandamp_Core_Guid();
        $catalogGuid = (isset($aData['guid']) && !empty($aData['guid']))? $aData['guid'] : $gman->generateGuid();
        $folderGuid = (isset($aData['folderGuid']) && !empty($aData['folderGuid']))? $aData['folderGuid'] : '';

        //if not empty, there are 2 possibilities
        $where = $tblCatalog->getAdapter()->quoteInto('guid=?', $catalogGuid);
        if($tblCatalog->fetchRow($where))
        {
            $rowCatalog = $tblCatalog->find($catalogGuid)->current();

            $rowCatalog->shortTitle = (isset($aData['shortTitle']))?$aData['shortTitle']:$rowCatalog->shortTitle;
            $rowCatalog->publishedDate = (isset($aData['publishedDate']))?$aData['publishedDate']:$rowCatalog->publishedDate;
            $rowCatalog->expiredDate = (isset($aData['expiredDate']))?$aData['expiredDate']:$rowCatalog->expiredDate;
            $rowCatalog->status = (isset($aData['status']))?$aData['status']:$rowCatalog->status;
            $rowCatalog->price = (isset($aData['price']))?$aData['price']:$rowCatalog->price;
        }
        else
        {
            $rowCatalog = $tblCatalog->fetchNew();

            $rowCatalog->guid = $catalogGuid;
            $rowCatalog->shortTitle = (isset($aData['shortTitle']))?$aData['shortTitle']:'';
            $rowCatalog->profileGuid = $aData['profileGuid'];
            $rowCatalog->publishedDate = (isset($aData['publishedDate']))?$aData['publishedDate']:'0000-00-00 00:00:00';
            $rowCatalog->expiredDate = (isset($aData['expiredDate']))?$aData['expiredDate']:'0000-00-00 00:00:00';
            $rowCatalog->createdBy = (isset($aData['username']))?$aData['username']:'';
            $rowCatalog->modifiedBy = $rowCatalog->createdBy;
            $rowCatalog->createdDate = date("Y-m-d h:i:s");
            $rowCatalog->modifiedDate = $rowCatalog->createdDate;
            $rowCatalog->deletedDate = '0000-00-00 00:00:00';
            $rowCatalog->status = (isset($aData['status']))?$aData['status']:0;
            $rowCatalog->price = (isset($aData['price']))?$aData['price']:0;
        }
        try
        {
            $catalogGuid = $rowCatalog->save();
        }
        catch (Exception $e)
        {
            die($e->getMessage());
        }

        $tableProfileAttribute = new App_Model_Db_Table_ProfileAttribute();
        $profileGuid = $rowCatalog->profileGuid;
        $where = $tableProfileAttribute->getAdapter()->quoteInto('profileGuid=?', $profileGuid);
        $rowsetProfileAttribute = $tableProfileAttribute->fetchAll($where,'viewOrder ASC');

        $rowsetCatalogAttribute = $rowCatalog->findDependentRowsetCatalogAttribute();
        foreach ($rowsetProfileAttribute as $rowProfileAttribute)
        {
            if($rowsetCatalogAttribute->findByAttributeGuid($rowProfileAttribute->attributeGuid))
            {
                $rowCatalogAttribute = $rowsetCatalogAttribute->findByAttributeGuid($rowProfileAttribute->attributeGuid);
            }
            else
            {
                $tblCatalogAttribute = new App_Model_Db_Table_CatalogAttribute();
                $rowCatalogAttribute = $tblCatalogAttribute->fetchNew();
                $rowCatalogAttribute->catalogGuid = $catalogGuid;
                $rowCatalogAttribute->attributeGuid = $rowProfileAttribute->attributeGuid;
            }

            $rowCatalogAttribute->value = (isset($aData[$rowProfileAttribute->attributeGuid]))?$aData[$rowProfileAttribute->attributeGuid]:'';

            $rowCatalogAttribute->save();
        }

        //save to table CatalogFolder only if folderGuid is not empty
        if (!empty($folderGuid))
        {
            $tblCatalogFolder = new App_Model_Db_Table_CatalogFolder();

            $rowsetCatalogFolder = $tblCatalogFolder->find($catalogGuid, $folderGuid);
            if(count($rowsetCatalogFolder)<=0)
            {
                $rowCatalogFolder = $tblCatalogFolder->createRow(array('catalogGuid'=>'', 'folderGuid'=>''));
                $rowCatalogFolder->catalogGuid = $catalogGuid;
                $rowCatalogFolder->folderGuid = $folderGuid;
                $rowCatalogFolder->save();
            }
        }


        //do indexing
        $indexingEngine = Pandamp_Search::manager();
        $indexingEngine->indexCatalog($catalogGuid);

        return $catalogGuid;
    }
    public function isBoughtByUser($catalogGuid, $userId)
    {
        $db = Pandamp_Application::getResource('multidb')->getDb('db1');

        $dbResult = $db->query("SELECT KOD.*, KO.datePurchased AS purchasingDate
                        FROM
                        KutuOrderDetail AS KOD,
                            KutuOrder AS KO
                        WHERE
                                KO.orderId = KOD.orderId
                        AND
                                userId = '$userId'
                        AND
                                (KO.orderStatus = 3
                                OR
                                KO.orderStatus = 5)
                        AND
                                itemId LIKE '$catalogGuid'");

        //LIMIT $offset, $limit");

        $aResult = $dbResult->fetchAll(Zend_Db::FETCH_ASSOC);
        //var_dump($aResult);
        //die();
        if(count($aResult) > 0)
                return true;
        else
                return false;
    }
    public function jCartIsItemSellable($catalogGuid)
    {
        //apakah pernah dibeli
        $hasBought = false;

        $auth = Zend_Auth::getInstance();

        if($auth->hasIdentity())
        {
            $bpm = new Pandamp_Core_Hol_Catalog();
            $hasBought = $bpm->isBoughtByUser($catalogGuid, $auth->getIdentity()->kopel);
        }
        if($hasBought)
        {
            $aReturn['isError'] = true;
            $aReturn['message'] = 'You have bought this Item before. Please check your account.';
            $aReturn['code'] = 1;
            return $aReturn;
        }

        Pandamp_Application::getResource('multidb');
        require_once ROOT_DIR.'/app/models/Db/Table/Catalog.php';
        require_once ROOT_DIR.'/app/models/Db/Table/Rowset/CatalogAttribute.php';
        require_once ROOT_DIR.'/app/models/Db/Table/Row/Catalog.php';
        
        // if status=draft then return false
        $tblCatalog = new App_Model_Db_Table_Catalog();
        $rowCatalog = $tblCatalog->find($catalogGuid)->current();
        if($rowCatalog)
        {
            if($rowCatalog->status != 99)
            {
                $aReturn['isError'] = true;
                $aReturn['message'] = 'This item is not ready to be bought yet.';
                $aReturn['code'] = 1;
                return $aReturn;
            }

            // if price <= 0 then return false
            if($rowCatalog->price <= 0)
            {
                $aReturn['isError'] = true;
                $aReturn['message'] = 'This item is for FREE.';
                $aReturn['code'] = 2;
                return $aReturn;
            }

            /*
            $tblRelatedItem = new Pandamp_Modules_Dms_Catalog_Model_RelatedItem();
            $where = "relatedGuid='$catalogGuid' AND relateAs='RELATED_FILE'";
            $rowsetRelatedItem = $tblRelatedItem->fetchAll($where);
            if(count($rowsetRelatedItem) > 0)
            {
                //check if the physical FILE is available in uploads directory.
                $flagFileFound = true;

                foreach($rowsetRelatedItem as $rowRelatedItem)
                {
                    $tblCatalog = new Pandamp_Modules_Dms_Catalog_Model_Catalog();
                    $rowsetCatalogFile = $tblCatalog->find($rowRelatedItem->itemGuid);

                    $rowCatalogFile = $rowsetCatalogFile->current();
                    $rowsetCatAtt = $rowCatalogFile->findDependentRowsetCatalogAttribute();

                    $contentType = $rowsetCatAtt->findByAttributeGuid('docMimeType')->value;
                    $systemname = $rowsetCatAtt->findByAttributeGuid('docSystemName')->value;
                    $filename = $rowsetCatAtt->findByAttributeGuid('docOriginalName')->value;

                    if(true)
                    {
                        $parentGuid = $rowRelatedItem->relatedGuid;
                        $sDir1 = ROOT_DIR.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$systemname;
                        $sDir2 = ROOT_DIR.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$parentGuid.DIRECTORY_SEPARATOR.$systemname;

                        if(file_exists($sDir1))
                        {
                            //$flagFileFound = true;
                        }
                        else
                            if(file_exists($sDir2))
                            {
                                    //$flagFileFound = true;
                            }
                            else
                            {
                                    $flagFileFound = false;
                            }
                    }
                }

                if($flagFileFound)
                {
                    $aReturn['isError'] = false;
                    $aReturn['message'] = 'This item is SELLABLE.';
                    $aReturn['code'] = 99;
                    return $aReturn;
                }
                else
                {
                    $aReturn['isError'] = true;
                    $aReturn['message'] = 'We are Sorry. The document(s) you are requesting is still under review. Please check back later.';
                    $aReturn['code'] = 5;
                    return $aReturn;
                }

            }
            else
            {
                $aReturn['isError'] = true;
                $aReturn['message'] = 'We are Sorry. The document(s) you are requesting is still being prepared. Please check back later.';
                $aReturn['code'] = 5;
                return $aReturn;
            }
            */


        }
        else
        {
            $aReturn['isError'] = true;
            $aReturn['message'] = 'Can not find your selected item(s).';
            $aReturn['code'] = 10;
            return $aReturn;
        }



        //if ada record related document, but tidak ada dokumen fisik, then return false

        // if tidak ada record related document (blm ada dokumen/file diupload), then return false

        // if pernah dibeli user sebelumnya, then return false

    }
    public function getPrice($catalogGuid)
    {
        $rowset = App_Model_Show_Catalog::show()->getCatalogByGuid($catalogGuid);
        if($rowset)
        {
            return $rowset['price'];

        }
        else
        {
            return 0;
        }
    }
    public function getDiscount($coupon)
    {
        $promo = new App_Model_Db_Table_Promotion();
        $rowset = $promo->find($coupon)->current();
        if($rowset)
        {
            return $rowset->discount;

        }
        else
        {
            return 0;
        }
    }

}
