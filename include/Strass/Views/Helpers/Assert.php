<?php

class Strass_View_Helper_Assert extends Zend_View_Helper_Url
{
  function assert($role = null, $resource = null, $action = null)
  {
    $acl = Zend_Registry::get('acl');
    if (!$role)
      $role = Zend_Registry::get('user');

    return $acl->isAllowed($role, $resource, $action);
  }
}
