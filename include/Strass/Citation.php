<?php

class Citation extends Strass_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
	protected $_name = 'citation';

	function __construct()
	{
		parent::__construct();
		$acl = Zend_Registry::get('acl');
		if (!$acl->has($this)) {
			$acl->add(new Zend_Acl_Resource($this->getResourceId()));
			$acl->allow('individus', $this, 'enregistrer');
		}
	}

	function getResourceId()
	{
		return 'citations';
	}
}