<?php

class Strass_Controller_Action_Exception extends Zend_Controller_Action_Exception
{
  function __construct($message=null, $code=500)
  {
    parent::__construct($message, $code);
  }
}
