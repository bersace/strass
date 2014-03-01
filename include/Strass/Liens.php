<?php

class Liens extends Zend_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
  protected $_name = 'lien';

  function getResourceId()
  {
    return 'liens';
  }

  function initAclResource($acl)
  {
    $acl = Zend_Registry::get('acl');
    $acl->add($this);
    // seuls les admins peuvent ajouter des liens, on n'ajoute donc
    // aucune permissions explictes.
  }
}
