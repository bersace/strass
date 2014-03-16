<?php

class Citation extends Strass_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
  protected $_name = 'citation';

  function initAclResource($acl)
  {
    $acl->add(new Zend_Acl_Resource($this->getResourceId()));
    $acl->allow('membres', $this, 'enregistrer');
  }

  function getResourceId()
  {
    return 'citations';
  }

  function findRandom()
  {
    $s = $this->select()->order('RANDOM()')->limit(1);
    return $this->fetchAll($s)->current();
  }
}
