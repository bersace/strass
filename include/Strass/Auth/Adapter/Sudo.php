<?php

require_once 'Zend/Auth/Result.php';

class Strass_Auth_Adapter_Sudo implements Zend_Auth_Adapter_Interface
{
	protected $current;
	protected $actual;
	protected $target;

	function __construct($current)
	{
		$this->actual = $current;
		$session = new Zend_Session_Namespace;
		$this->actual = isset($session->actual_user) ? $session->actual_user : $current;
		$this->current = $current;
		$this->target = null;
	}

	function setTarget($target)
	{
		$this->target = $target;
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
		$session->actual_user = $this->actual;

		return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS,
					    $this->current);
	}

	function unsudo()
	{
		$unsudo = $this->current != $this->actual;

		$this->current = $this->actual;
		$this->target = null;
		$session = new Zend_Session_Namespace;
		$session->actual_user = $this->actual;

		return $unsudo;
	}

	function __set($name, $value)
	{
		switch($name) {
		case 'target':
			$this->$name = $value;
			break;
		}
	}

	function __get($name)
	{
		switch($name) {
		case 'current':
		case 'actual':
		case 'target':
			return $this->$name;
			break;
		}
	}
}