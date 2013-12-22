<?php

  // menu
$menu = $this->addons->addSection ('menu', 'Menu');
$list = $menu->addChild (new Wtk_List ());

// réinitialiser tout les paramètres de la requête pour ne pas avoir
// de confusion.
$controller = Zend_Controller_Front::getInstance()->getRequest()->getParam('controller');
$controller = $controller == 'index' ? null : $controller;

foreach ($this->menu as $item) {
	if (isset($item['private']) && Zend_Registry::get('user')->username == 'nobody')
		continue;

	$urlOptions = $item['url'];
	$link = new Wtk_Link ($this->url($urlOptions, true),
			      new Wtk_Metas ($item['metas']));
	$i = $list->addItem($link);
	$i->addFlags($urlOptions['controller'] ? $urlOptions['controller'] : 'index',
		     $controller == $urlOptions['controller'] ? 'current' : null);
}
