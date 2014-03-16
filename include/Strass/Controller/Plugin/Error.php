<?php

require_once 'Orror.php';

class Strass_Controller_Plugin_Error extends Zend_Controller_Plugin_Abstract
{
  public function routeStartup()
  {
    if (Strass_Version::onDevelopment()) {
      $level = E_ALL &~ (E_STRICT|E_DEPRECATED);
      ini_set('display_errors', 1);
    }
    else {
      $level = 0;
      init_set('display_errors', 0);
    }

    Orror::init($level,
		array($this, 'errorHandler'),
		array($this, 'kill'),
		false);
  }

  public function errorHandler($msg, $file, $line, $class, $function, $level, $backtrace = array(),
			       $exception = NULL)
  {
    $args = func_get_args();
    ob_start();
    call_user_func_array(array('Orror', 'output'), $args);
    $error = ob_get_contents();
    ob_end_clean();

    $this->_response->appendBody($error);
  }

  public function kill()
  {
    $this->_response->outputBody();
    die();
  }
}
