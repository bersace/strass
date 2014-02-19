<?php

class Strass_ActionLogger extends Strass_Logger
{
  function __construct($controller)
  {
    $request = $controller->getRequest();
    parent::__construct($request->getControllerName());
    $this->default_url = $request->REQUEST_URI;
  }

  function log($level, $message, $url=null, $detail=null)
  {
    if (!$url) {
      $url = $this->default_url;
    }

    if (is_array($url)) {
      $router = Zend_Controller_Front::getInstance()->getRouter();
      $url = $router->assemble($url, null, $reset);
    }

    return parent::log($level, $message, $url, $detail);
  }
}
