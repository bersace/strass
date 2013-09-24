<?php

class Wtk_Form_Model_Exception extends Exception
{
  protected $format;
  protected $instance;

  function __construct ($format, Wtk_Form_Model_Instance $instance = null)
  {
    parent::__construct ($instance ? sprintf ($format, $instance->label) : $format);
    $instance->valid = FALSE;
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