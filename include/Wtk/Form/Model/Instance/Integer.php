<?php

class Wtk_Form_Model_Instance_Integer extends Wtk_Form_Model_Instance
{
  function __construct ($path, $label, $value, $min = 0, $max = PHP_INT_MAX)
  {
    parent::__construct ($path, $label, $value);
  }

  function retrieve ($value)
  {
	  if ($this->readonly)
	    return true;

    $this->value = intval($value);
    return TRUE;
  }
}
