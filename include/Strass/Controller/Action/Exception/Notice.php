<?php

class Strass_Controller_Action_Exception_Notice extends Strass_Controller_Action_Exception
{
  function __construct($message=null, $code=200, $aide=null)
  {
    parent::__construct($message, $code, $aide);
  }
}
