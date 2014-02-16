<?php

require_once 'Strass/Addon.php';

class Strass_Addon_Menu extends Strass_Addon_Liens
{
  static $menu = array (array('metas' => array('label' => 'Accueil'),
			      'url'   => array('controller' => 'index')),
			array('metas' => array('label' => "Livre d'or"),
			      'url'   => array('controller' => 'livredor')),
			array('metas' => array('label' => 'Liens'),
			      'url'   => array('controller' => 'liens')),
			array('metas' => array('label' => 'Administration'),
			      'url'   => array('controller' => 'admin'),
			      'acl'   => array(null, 'site', 'admin')),
			);

  function __construct()
  {
    parent::__construct('menu', 'Menu');
  }

  public function initView($view)
  {
    foreach (self::$menu as $item) {
      $acl = array_key_exists('acl', $item) ? $item['acl'] : array();
      $this->append($item['metas'], $item['url'], $acl, true);
    }

    return parent::initView($view);
  }
}
