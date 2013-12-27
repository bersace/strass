<?php

class Wtk_Form_Model_Exception extends Exception
{
  protected $format;
  protected $instance;

  function __construct ($format, Wtk_Form_Model_Instance $instance = null)
  {
    if ($instance) {
      $instance->valid = FALSE;
      $format = sprintf($format, $instance->label);
    }
    parent::__construct($format);
    $this->format = $format;
    $this->instance = $instance;
  }

  function getInstance ()
  {
    return $this->instance;
  }

  function getFormat ()
  {
    return $this->format;
  }
}

?>