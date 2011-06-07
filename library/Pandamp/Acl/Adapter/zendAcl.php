<?php

/**
 *
 * @package 	Anoa
 * @author      Nihki Prihadi <nihki@madaniyah.com>
 * @version     1.0
 * @since		2009-10-07 18:10:22Z
 * @todo 		Access Control List (ACL)
 * @abstract
 * 
 */

class Pandamp_Acl_Adapter_zendAcl extends Zend_Acl
{
	private $dbase;
	
	public $_getUserRoleName = null;
	
	public $_getUserRoleId = null;
	
	public function __construct( Zend_Db_Adapter_Abstract &$db ){
		$this->dbase = &$db;
	}
    private function initRoles() 
    { 
        $roles = $this->dbase->fetchAll( 
        $this->dbase->select() 
            ->from('acl_roles') 
            ->order(array('role_id DESC'))); 
 
       	if (!$this->hasRole(new Zend_Acl_Role($roles[0]['role_name']))) {
        	$this->addRole(new Zend_Acl_Role($roles[0]['role_name'])); 
 
	        for ($i = 1; $i < count($roles); $i++) { 
	            $this->addRole(new Zend_Acl_Role($roles[$i]['role_name']), $roles[$i-1]['role_name']); 
	        } 
       	}
    } 
 
    private function initResources() 
    { 
        self::initRoles(); 
 
        $resources = $this->dbase->fetchAll( 
        $this->dbase->select() 
            ->from('acl_resources')); 
 
        foreach ($resources as $key=>$value){ 
            if (!$this->has($value['resource'])) { 
               	$this->add(new Zend_Acl_Resource($value['resource'])); 
            } 
        } 
    } 
 
    private function roleResource() 
    { 
        self::initResources(); 
 
        $acl = $this->dbase->fetchAll( 
        $this->dbase->select() 
            ->from('acl_roles') 
            ->from('acl_resources') 
            ->from('acl_permissions') 
            ->where('acl_roles.role_id = acl_permissions.role_id')); 
 
        foreach ($acl as $key=>$value) { 
            $this->allow($value['role_name'], $value['resource'],$value['permission']); 
        } 
    } 
    
    public function getUserGroup($username)
    {
		$username = $username ? $username : 'Guest';
		
        self::roleResource(); 
 
        $getUserRole = $this->dbase->fetchRow( 
        $this->dbase->select() 
            ->from('acl_roles') 
            ->from('acl_users') 
            ->where('acl_users.user_name = "' . $username . '"') 
            ->where('acl_users.role_id = acl_roles.role_id')); 
 
        //$this->_getUserRoleId = $getUserRole['role_id'] ? $getUserRole['role_id'] : 4; 
        $getUserRoleName = $getUserRole['role_name'] ? $getUserRole['role_name'] : 'User'; 
 		if (!$this->hasRole(new Zend_Acl_Role($username))) {
        	$this->addRole(new Zend_Acl_Role($username), $getUserRoleName); 
 		}
        return $getUserRoleName;
    }
 
    public function listRoles() 
    { 
        return $this->dbase->fetchAll( 
        $this->dbase->select() 
            ->from('acl_roles')); 
    } 
 
    public function getRoleId($roleName) 
    { 
        return $this->dbase->fetchRow( 
        $this->dbase->select() 
            ->from('acl_roles', 'role_id') 
            ->where('acl_roles.role_name = "' . $roleName . '"')); 
    } 
    
    public function getRoleName($roleId)
    {
    	return $this->dbase->fetchRow(
    	$this->dbase->select()
    		->from('acl_roles','role_name')
    		->where('acl_roles.role_id = "' . $roleId . '"'));
    }
 
    public function insertAclUser($username, $getRoleName) 
    { 
    	$getUserRoleId = $this->getRoleId($getRoleName);
    	
        $data = array( 
            'role_id' => $getUserRoleId['role_id'], 
            'user_name' => $username); 
 
        return $this->dbase->insert('acl_users',$data); 
    } 
    
    public function deleteAclUser($username)
    {
        $userAcl = $this->dbase->fetchRow( 
        $this->dbase->select() 
            ->from('acl_users') 
            ->where('user_name = "' . $username . '"')); 
		
       	return $this->dbase->delete('acl_users',"user_name='".$userAcl['user_name']."'");
    }
 
    public function listResources() 
    { 
        return $this->dbase->fetchAll( 
        $this->dbase->select() 
            ->from('acl_resources') 
            ->from('acl_permissions') 
            ->where('resource_uid = uid')); 
    } 
 
    public function listResourcesByGroup($group) 
    { 
        $result = null; 
        $group = $this->dbase->fetchAll($this->dbase->select() 
            ->from('acl_resources') 
            ->from('acl_permissions') 
            ->where('acl_resources.resource = "' . $group . '"') 
            ->where('uid = resource_uid') 
        ); 
 
        foreach ($group as $key=>$value) { 
            if($this->isAllowed($username, $value['resource'], $value['permission'])) { 
                $result[] = $value['permission']; 
            } 
        } 
 
        return $result; 
    } 
 
    public function isUserAllowed($username, $resource, $permission) 
    { 
//    	if (!$this->has($this->getUserGroup($username))) {
    	$this->getUserGroup($username);
        return ($this->isAllowed($username, $resource, $permission)); 
//    	}
    } 	
}

?>