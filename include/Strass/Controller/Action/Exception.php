<?php

class Strass_Controller_Action_Exception extends Zend_Controller_Action_Exception
{
  function __construct($message=null, $code=500)
  {
    $args = func_get_args();
    call_user_func_array(array('parent', '__construct'), $args);
  }
}
