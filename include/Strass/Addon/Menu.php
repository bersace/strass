<?php

require_once 'Strass/Addon.php';

class Strass_Addon_Menu extends Strass_Addon
{
  public function initView($view)
  {
    $config = new Strass_Config_Php('strass');
    $view->menu = $config->menu->toArray();
  }

}
