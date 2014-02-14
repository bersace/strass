<?php

require_once 'Strass/Log.php';

class Strass_Logger
{
  function __construct($controller)
  {
    $this->controller = $controller;
    $this->table = new Logs;
  }

  function log($level, $message, $url=null, $detail=null)
  {
    $user = Zend_Registry::get('user');
    $request = $this->controller->getRequest();

    if (!$url)
      $url = $request->REQUEST_URI;
    if (is_array($url)) {
      $url = $this->controller->_helper->Url->url($url, null, true);
    }

    $logger = strtolower(join('.', array($request->getModuleName(),
					 $request->getControllerName(),
					 $request->getActionName())));
    // insertion du tuple
    $data = array('logger' => $logger,
		  'level' => $level,
		  'user' => $user->id,
		  'message' => $message,
		  'url' => $url,
		  'detail' => var_export($detail, true),
		  );
    return $this->table->insert($data);
  }

  function info()
  {
    $args = func_get_args();
    array_unshift($args, Logs::LEVEL_INFO);
    return call_user_func_array(array($this, 'log'), $args);
  }

  function warn()
  {
    $args = func_get_args();
    array_unshift($args, Logs::LEVEL_WARNING);
    return call_user_func_array(array($this, 'log'), $args);
  }

  function error()
  {
    $args = func_get_args();
    array_unshift($args, Logs::LEVEL_ERROR);
    return call_user_func_array(array($this, 'log'), $args);
  }
}
