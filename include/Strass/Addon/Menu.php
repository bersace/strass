<?php

require_once 'Strass/Addon.php';

class Strass_Addon_Menu extends Strass_Addon_Liens
{
  static $menu = array (array('metas' => 'Accueil',
			      'url'   => array()),
			array('metas' => "Livre d'or",
			      'url'   => array('controller' => 'livredor')),
			array('metas' => 'Liens',
			      'url'   => array('controller' => 'liens')),
			array('metas' => 'Administration',
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

    $t = new Unites;
    $racines = $t->findRacines();
    foreach($racines as $i => $u) {
      if (!$i)
	continue; // On saute l'unité racine par défaut

      $this->append($u->getName(), array('unite' => $u->slug), array(), true);
    }

    return parent::initView($view);
  }
}
