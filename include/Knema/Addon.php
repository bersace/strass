<?php

abstract class Knema_Addon
{
  public function initView($view)
  {
  }

  public function viewScript()
  {
      $c = explode('_', get_class($this));
      return strtolower($c[2]);
  }
}

