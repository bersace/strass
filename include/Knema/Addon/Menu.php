<?php

require_once 'Knema/Addon.php';

class Knema_Addon_Menu extends Knema_Addon
{
  public function initView($view)
  {
    $config = new Knema_Config_Php('strass');
    $view->menu = $config->menu->toArray();
  }

}
