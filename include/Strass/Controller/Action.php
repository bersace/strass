<?php

require_once 'Strass/Users.php';

abstract class Strass_Controller_Action extends Knema_Controller_Action implements Zend_Acl_Resource_Interface
{
	protected	$resourceid;

	public function getResourceId()
	{
		return $this->resourceid;
	}

	public function init()
	{
		parent::init();

		$page = Zend_Registry::get('page');
		$this->connexes = $page->addon(new Strass_Addon_Connexes);
		$this->actions = $page->addon(new Strass_Addon_Console($this->_helper->Auth));
		$page->addon(new Strass_Addon_Citation);
	}

	function assert($role = null, $resource = null, $action = null, $message = null)
	{
		$role = $role ? $role : Zend_Registry::get('individu');
		if (!$role && $message) {
			$this->_helper->Auth->http();
			$role = $this->_helper->Individu->auth();
		}
		return parent::assert($role, $resource, $action, $message);
	}
}
