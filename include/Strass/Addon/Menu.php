<?php

require_once 'Strass/Addon.php';

class Strass_Addon_Menu extends Strass_Addon
{
  static $menu = array (array('metas' => array('label' => 'Accueil'),
			      'url'   => array('controller' => 'index')),
			array('metas' => array('label' => "Livre d'or"),
			      'url'   => array('controller' => 'livredor')),
			array('metas' => array('label' => 'Liens'),
			      'url'   => array('controller' => 'liens')),
			);

  public function initView($view)
  {
    $acl = Zend_Registry::get('acl');
    $user = Zend_Registry::get('user');

    $m = array();
    foreach (self::$menu as $item) {
      if (array_key_exists('acl', $item)) {
	list($role, $resource, $action) = $item['acl'];
	if (!$role) $role = $user;
	if (!$acl->isAllowed($role, $resource, $action))
	  continue;
      }
      $m[] = $item;
    }
    $view->menu = $m;
  }
}
