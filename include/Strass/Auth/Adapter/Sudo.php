<?php

require_once 'Zend/Auth/Result.php';

class Strass_Auth_Adapter_Sudo implements Zend_Auth_Adapter_Interface
{
	public $current;
	public $actual;
	public $target;

	function __construct($current)
	{
		$this->actual = $current;
		$session = new Zend_Session_Namespace;
		$t = new Users;
		$this->actual = isset($session->actual_user) ? $t->findByUsername($session->actual_user) : $current;
		$this->current = $current;
		$this->target = null;
	}

	function authenticate()
	{
		$acl = Zend_Registry::get('acl');
		if (!$acl->isAllowed($this->actual, $this->target, 'admin'))
			return Zend_Auth_Result(Zend_Auth_Result::FAILURE,
						$this->current,
						"Vous n'avez pas le droit de prendre cette identité.");

		$this->current = $this->target;
		$session = new Zend_Session_Namespace;
		$session->actual_user = $this->actual->username;

		return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->current->getIdentity());
	}

	function unsudo()
	{
		$unsudo = $this->current != $this->actual;

		$this->current = $this->actual;
		$this->target = null;
		$session = new Zend_Session_Namespace;
		$session->actual_user = $this->actual->username;

		return $unsudo;
	}
}