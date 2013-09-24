<?php

class Knema_Controller_Action_Helper_Errors extends Zend_Controller_Action_Helper_Abstract
{
  protected $response;

  public function init()
  {
    Orror::init(E_ALL,
		array($this, 'errorHandler'),
		array($this, 'kill'),
		false);
  }

  public function errorHandler($msg, $file, $line, $class, $function, $level, $backtrace = array(), $exception = NULL)
  {
    $args = func_get_args();
    ob_start();
    call_user_func_array(array('Orror', 'output'), $args);
    $error = ob_get_contents();
    ob_end_clean();

    $this->response->appendBody($error);
  }

  public function kill()
  {
    $this->response->outputBody();
    die();
  }

  public function direct($response)
  {
    $this->response = $response;
  }
}
