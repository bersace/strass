<?php

class Knema_View_Helper_Assert
{
	public function assert($role, $resource, $action)
	{
		$acl = Zend_Registry::get('acl');
		$role = $role ? $role : Zend_Registry::get('user');
		return $acl->isAllowed($role, $resource, $action);
	}
}
