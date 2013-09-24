<?php

class Liens extends Zend_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
  protected	$_name		= 'liens';
  protected	$_rowClass	= 'Lien';

  function __construct()
  {
    parent::__construct();
    $acl = Zend_Registry::get('acl');
    if (!$acl->has($this)) {
      $acl->add($this);
      // seuls les admins peuvent ajouter des liens, on n'ajoute donc
      // aucune permissions explictes.
    }
  }

  function getResourceId()
  {
    return 'liens';
  }
}

class Lien extends Zend_Db_Table_Row_Abstract
{
}
