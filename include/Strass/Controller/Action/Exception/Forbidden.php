<?php

class Strass_Controller_Action_Exception_Forbidden extends Strass_Controller_Action_Exception
{
  function __construct($message=null, $code=403)
  {
    $args = func_get_args();
    call_user_func_array(array('parent', '__construct'), $args);
  }
}
