<?php

class Strass_Acl extends Zend_Acl
{
  public function isAllowed($role = null, $resource = null, $privilege = null)
  {
    if (!$role)
      $role = Zend_Registry::get('user');

    if (!$this->has($resource) && method_exists($resource, 'initAclResource'))
      $resource->initAclResource($this);

    return parent::isAllowed($role, $resource, $privilege);
  }
}
