<?php

class Strass_Controller_Action_Exception_Authentification extends Strass_Controller_Action_Exception
{
  function __construct($message=null)
  {
    parent::__construct($message, 401);
  }
}