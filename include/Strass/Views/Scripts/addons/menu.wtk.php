<?php

$menu = $this->addons->addSection ('menu', 'Menu');
$list = $menu->addList();

foreach ($this->menu as $item) {
  extract($item);
  $url = array_merge(array('controller' => null, 'action' => null),
		     $url);
  $list->addItem()->addFlags($url['controller'], $url['action'])
    ->addLink($this->url($url, true), new Wtk_Metas($metas));
}
