<?php

require_once 'Knema/Users.php';

class FakeUser extends User implements Zend_Acl_Resource_Interface
{
	protected $individu;
	protected $id;
	protected $username = 'fakeuser';

	function __construct($individu)
	{
		$this->individu = $individu;
		$this->id = $individu->id;
		$acl = Zend_Registry::get('acl');
		if (!$acl->has($this))
			$acl->add(new Zend_Acl_Resource($this->getResourceId()));
	}

	function __sleep()
	{
		return array('id');
	}

	function __wakeup()
	{
		$ti = new Individus;
		$this->individu = $ti->find($this->id)->current();

		$acl = Zend_Registry::get('acl');
		if (!$acl->has($this))
			$acl->add($this);

		if (!$acl->hasRole($this))
			$acl->addRole($this);
	}

	function getResourceId()
	{
		return 'fakeuser-'.$this->individu->id;
	}

	function getRoleId()
	{
		return 'fakeuser-'.$this->individu->id;
	}

	function save()
	{
	}

	function __get($name)
	{
		switch($name) {
		case 'individu':
			return $this->individu;
			break;
		}
	}

	function __toString()
	{
		$this->username;
	}
}