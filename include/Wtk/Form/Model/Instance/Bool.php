<?php

class Wtk_Form_Model_Instance_Bool extends Wtk_Form_Model_Instance
{
  function __construct ($path, $label, $value = FALSE)
  {
    parent::__construct ($path, $label, $value);
  }

  function retrieve ($value)
  {
    $this->value = (bool) $value;
    return TRUE;
  }
}

?>