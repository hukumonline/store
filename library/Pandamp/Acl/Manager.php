<?php
class Pandamp_Acl_Manager
{
	static function getAdapter()
	{
		$config = Zend_Registry::get(Pandamp_Keys::REGISTRY_CONFIG_OBJECT);
		
		switch (strtolower($config->acl->adapter))
		{
			case 'phpgacl':
				$aclAdapter = new Pandamp_Acl_Adapter_PhpGacl($config->acl->adapter, $config->acl->params->toArray());
				return $aclAdapter;
			default :
				throw new Zend_Exception('Pandamp_Acl does not support adapter: '. $config->acl->adapter. '. Please check your configuration.', 101);
		}
	}
}
?>