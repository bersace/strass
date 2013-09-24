<?php

class Users extends Zend_Db_Table_Abstract implements Zend_Acl_Resource_Interface, Zend_Acl_Role_Interface
{
	protected	$_name		= 'users';
	protected	$_rowClass	= 'User';
	protected	$_dependentTables	= array('Memberships');

	function __construct()
	{
		parent::__construct();

		$acl = Zend_Registry::get('acl');
		if (!$acl->hasRole($this))
			$acl->addRole(new Zend_Acl_Role($this->getRoleId()));
		if (!$acl->has($this))
			$acl->add(new Zend_Acl_Resource($this->getResourceId()));
	}

	function getRoleId()
	{ return 'membres'; }

	function getResourceId()
	{ return 'membres'; }

	function register($username, $realm, $password, $arealm)
	{
		$data = array('username'	=> $username,
			      'password'	=> md5($password),
			      'ha1'		=> self::ha1($username, $realm, $password, $arealm));
		$this->insert($data);
	}

	static function ha1($username, $realm, $password, $arealm = '')
	{
		return hash('md5', $username.':'.$realm.$arealm.':'.$password);
	}
}

class User extends Zend_Db_Table_Row_Abstract implements Zend_Acl_Role_Interface, Zend_Acl_Resource_Interface
{
	function __construct($config)
	{
		parent::__construct($config);

		$acl = Zend_Registry::get('acl');
		if (!$acl->has($this))
			$acl->add(new Zend_Acl_Resource($this->getResourceId()));

		if (!$acl->hasRole($this)) {
			$groups = $this->findGroupsViaMemberships();

			$parents = array();
			foreach($groups as $group)
				$parents[] = $group;

			$acl->addRole(new Zend_Acl_Role($this->getRoleId()), $parents);
		}
	}

	public function getIdentity ()
	{
		return $this->username;
	}

	public function getRoleId ()
	{
		return $this->username;
	}

	public function getResourceId ()
	{
		return $this->username;
	}

	function setPassword($password, $realm, $arealm = '')
	{
		$this->password = hash('md5', $password);
		$this->ha1 = Users::ha1($this->username, $realm, $password, $arealm);
		$this->save();
	}
}

class Memberships extends Zend_Db_Table_Abstract
{
	protected	$_name		= 'membership';
	protected	$_referenceMap	= array('User'	=> array('columns'		=> 'username',
								 'refTableClass'	=> 'Users',
								 'refColumns'		=> 'username',
								 'onUpdate'		=> self::CASCADE,
								 'onDelete'		=> self::CASCADE),
						'Group'	=> array('columns'		=> 'groupname',
								 'refTableClass'	=> 'Groups',
								 'refColumns'		=> 'groupname',
								 'onUpdate'		=> self::CASCADE,
								 'onDelete'		=> self::CASCADE));
}


class Groups extends Zend_Db_Table_Abstract
{
	protected	$_name		= 'groups';
	protected	$_rowClass	= 'Group';
	protected	$_dependentTables	= array('Memberships');
}

class Group extends Zend_Db_Table_Row_Abstract implements Zend_Acl_Role_Interface
{
	function __construct($config)
	{
		parent::__construct($config);
		$acl = Zend_Registry::get('acl');
		if (!$acl->hasRole($this)) {
			$acl->addRole(new Zend_Acl_Role($this->getRoleId()));

			// Permettre tout aux admins :)
			if ($this->getRoleId() == 'admins')
				$acl->allow('admins');
		}
	}

	public function getRoleId()
	{
		return $this->groupname;
	}
}
