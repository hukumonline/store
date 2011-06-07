<?php

/**
 * Description of Form
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Pandamp_Lib_Form
{
    /**
     * province
     */
    function chooseProvince($province=null)
    {
        $tblProvince = new App_Model_Db_Table_State();
        $row = $tblProvince->fetchAll();

        $select_province = "<select name=\"province\" id=\"province\">\n";
        if ($province) {
            $rowProvince = $tblProvince->find($province)->current();
            $select_province .= "<option value='$rowProvince->pid' selected>$rowProvince->pname</option>";
            $select_province .= "<option value =''>----- Pilih -----</option>";
        } else {
            $select_province .= "<option value ='' selected>----- Pilih -----</option>";
        }
        
        foreach ($row as $rowset) {
            if (($province) and ($rowset->pid == $rowProvince->pid)) {
                continue;
            } else {
                $select_province .= "<option value='$rowset->pid'>$rowset->pname</option>";
            }
        }

        $select_province .= "</select>\n\n";
        return $select_province;
    }
}
