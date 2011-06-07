<?php

/**
 * Description of Group
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Pandamp_Controller_Action_Helper_UserGroup
{
    public function userGroup($packageId)
    {
        $acl = App_Model_Show_AroGroup::show()->getUserGroup($packageId);
        return $acl['name'];
    }
}
