<?php

class Strass_Controller_Action_Helper_Auth extends Zend_Controller_Action_Helper_Abstract
{
  protected $plugin;

  public function init()
  {
    $fc = Zend_Controller_Front::getInstance();
    $this->plugin = $fc->getPlugin('Strass_Controller_Plugin_Auth');
  }

  function sudo($target)
  {
    $this->plugin->sudo->target = $target;
    return $this->plugin->sudo();
  }

  function unsudo()
  {
    return $this->plugin->unsudo();
  }

  /* Authentification via HTTP. */
  function http()
  {
    return $this->plugin->http();
  }

  function direct()
  {
    return $this->plugin->getUser();
  }
}
