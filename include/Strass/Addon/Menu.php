<?php

require_once 'Strass/Addon.php';

class Strass_Addon_Menu extends Strass_Addon_Liens
{
    static $menu = array(
        array(
            'metas' => 'Accueil',
            'url'   => array()),
        array(
            'metas' => 'Annuaire',
            'url'   => array('controller' => 'individus'),
            'acl'   => array(null, 'membres', 'voir'),
        ),
        array(
            'metas' => 'Liens',
            'url'   => array('controller' => 'liens')),
    );

    function __construct()
    {
        parent::__construct('menu', 'Navigation');
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

            $this->append($u->getFullName(), array('unite' => $u->slug), array(), true);
        }

        parent::initView($view);
        $view->parent = $view->document->footer->current();
    }
}
