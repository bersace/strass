<?php

class Strass_Controller_Action_Exception_Notice extends Strass_Controller_Action_Exception
{
  function __construct($message=null, $code=200)
  {
    parent::__construct($message, $code);
  }
}
