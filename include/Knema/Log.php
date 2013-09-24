<?php

class Logs extends Zend_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
	protected	$_name		= 'log';
	protected	$_dependentTables	= array('LogAttrs');

	function __construct()
	{
		parent::__construct();

		$acl = Zend_Registry::get('acl');
		if (!$acl->has($this))
			$acl->add(new Zend_Acl_Resource($this->getResourceId()));
	}

	function getResourceId()
	{ return 'log'; }
}

class LogAttrs extends Zend_Db_Table_Abstract
{
	protected	$_name		= 'log_attr';
	protected	$_referenceMap	= array('Log'	=> array('columns'		=> 'log',
								 'refTableClass'	=> 'Log',
								 'refColumns'		=> 'id',
								 'onUpdate'		=> self::CASCADE,
								 'onDelete'		=> self::CASCADE));
}

