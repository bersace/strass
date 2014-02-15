<?php

class Strass_ActionLogger extends Strass_Logger
{
  function __construct($controller)
  {
    $this->controller = $controller;
    $request = $controller->getRequest();
    $name = strtolower(join('.', array($request->getModuleName(),
					     $request->getControllerName(),
					     $request->getActionName())));
    parent::__construct($name);
  }

  function log($level, $message, $url=null, $detail=null)
  {
    if (!$url) {
      $request = $this->controller->getRequest();
      $url = $request->REQUEST_URI;
    }

    if (is_array($url)) {
      $url = $this->controller->_helper->Url->url($url, null, true);
    }

    return parent::log($level, $message, $url, $detail);
  }
}
