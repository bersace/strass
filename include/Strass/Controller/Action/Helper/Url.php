<?php

class Strass_Controller_Action_Helper_Url extends Zend_Controller_Action_Helper_Url
{
  /*
   * Retourne une URL complète : "http://".$host.$base.$url;
   */
  public function full($action = null, $controller = null, $module = null, $params = array())
  {
    $request = $this->getRequest();
    $params = (array) $params;
    // merge args into $params;
    $var = array('action', 'controller', 'module');
    foreach($var as $v) {
      if ($$v)
	$params[$v] = $$v;
    }

    $url = $this->url($params);

    return "http://".$request->getServer('HTTP_HOST').$url;
  }

  /* Pour free, on désactive le contournement du routeur */
  public function simple()
  {
    $args = func_get_args();
    return call_user_func_array(array($this, 'full'), $args);
  }
}
