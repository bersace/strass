<?php

class Knema_Controller_Action_Helper_Acl extends Zend_Controller_Action_Helper_Abstract
{
	public function init()
	{
		if (!Zend_Registry::isRegistered('acl')) {
			$acl = new Zend_Acl();
			Zend_Registry::set('acl', $acl);
		}
	}

	public function direct()
	{
		return Zend_Registry::get('acl');
	}
}
