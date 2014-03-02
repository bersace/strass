<?php

abstract class Strass_Db_Table_Row_Abstract extends Zend_Db_Table_Row_Abstract
{
  protected $_privileges = array();

  function init()
  {
    if (!$this->_data) {
      $this->_data = array_fill_keys($this->getTable()->getColumns(), null);
    }
  }

  function initPrivileges($acl, $unites)
  {
    foreach ($unites as $unite) {
      foreach ($this->_privileges as $priv) {
	list($role, $privileges) = $priv;
	$role = $unite->getRoleId(is_null($role) ? 'membre' : $role);
	if ($acl->hasRole($role))
	  $acl->allow($role, $this, $privileges);
      }
    }
  }
}