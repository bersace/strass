<?php

class Strass_Controller_Action_Exception_Forbidden extends Strass_Controller_Action_Exception
{
  function __construct($message=null, $code=403)
  {
    parent::__construct($message, $code);
  }
}
