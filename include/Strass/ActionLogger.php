<?php

class Strass_ActionLogger extends Strass_Logger
{
  function __construct($controller)
  {
    $this->controller = $controller;
    parent::__construct($controller->getRequest()->getControllerName());
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
