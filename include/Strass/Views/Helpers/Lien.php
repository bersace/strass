<?php

class Strass_View_Helper_Lien
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function lien($url = null, $label = null, $reset = false)
  {
    $url = is_array($url) ? $this->view->url($url, $reset) : $url;
    return new Wtk_Link($url, $label);
  }
}
