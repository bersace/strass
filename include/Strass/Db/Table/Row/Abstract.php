<?php

abstract class Strass_Db_Table_Row_Abstract extends Zend_Db_Table_Row_Abstract
{
  protected $_privileges = array();

  function initResourceAcl($unites = null)
  {
    $acl = Zend_Registry::get('acl');
    if (!$acl->has($this)) {
      $info = $this->_table->info();
      $name = $info[Zend_Db_Table_Abstract::NAME];

      if (is_null($unites)) {
	$tables = $info[Zend_Db_Table_Abstract::DEPENDENT_TABLES];
	if (in_array('Unites', $tables)) {
	  $unites = $this->findUnites();
	}
	else {
	  $unites = array();
	  foreach ($info[Zend_Db_Table_Abstract::REFERENCE_MAP] as $ref)
	  {
	    if ($ref[Zend_Db_Table_Abstract::REF_TABLE_CLASS] == 'Unites') {
	      $unites = array($this->findParentUnites());
	      break;
	    }
	  }
	}
      }

      $acl->add(new Zend_Acl_Resource($this->getResourceId()));
      $this->_initResourceAcl($acl);
      foreach ($unites as $unite) {
	foreach ($this->_privileges as $priv) {
	  $rid = $unite->getRoleId($priv[0]);
	  if (is_null($priv[1]) && $acl->hasRole($rid)) {
	    $acl->allow($rid, $this);
	  }
	  else {
	    $actions = (array) $priv[1];
	    foreach($actions as $action)
	      if ($acl->hasRole($rid))
		$acl->allow($rid, $this, $action);
	  }
	}
      }
    }
  }

  /* appelé juste après avoir ajouté $this à $acl */
  protected function _initResourceAcl(&$acl)
  {
  }

  function initRoleAcl()
  {
    $acl = Zend_Registry::get('acl');
    if($acl->hasRole($this))
      return;

    //$acl->addRole(new Zend_Acl_Role($this->getRoleId()), $this->_parentRoles());
    $this->_initRoleAcl($acl);
  }

  protected function _parentRoles()
  {
    return array();
  }

  protected function _initRoleAcl($acl)
  {
  }
}