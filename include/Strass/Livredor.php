<?php

class Livredor extends Strass_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
	protected	$_name			= 'livredor';
	protected	$_rowClass		= 'Message';

	function __construct($config = array())
	{
		parent::__construct($config);
		// utiliser DI pour singleton ?
		$acl = Zend_Registry::get('acl');
		if (!$acl->has($this)) {
			$acl->add($this);
		}
	}

	function getResourceId()
	{
		// suffixer avec knema/site/id ?
		return 'livredor';
	}
}

class Message extends Zend_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
	function getResourceId()
	{
		// suffixer avec knema/site/id ?
		return 'livredor';
	}
}