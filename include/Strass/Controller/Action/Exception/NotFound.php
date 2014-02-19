<?php

class Strass_Controller_Action_Exception_NotFound extends Strass_Controller_Action_Exception_Notice
{
  function __construct($message=null, $code=404)
  {
    parent::__construct($message, $code);
  }
}
