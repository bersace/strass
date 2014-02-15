<?php

require_once 'Strass/Addon.php';

class Strass_Addon_Menu extends Strass_Addon
{
  public function initView($view)
  {
    $config = Zend_Registry::get('config');
    $acl = Zend_Registry::get('acl');
    $user = Zend_Registry::get('user');

    $m = array();
    foreach ($config->menu as $item) {
      if ($item->acl) {
	list($_, $resource, $action) = $item->acl->toArray();
	if (!$acl->isAllowed($user, $resource, $action))
	  continue;
      }
      $m[] = $item->toArray();
    }
    $view->menu = $m;
  }
}
