<?php

class Wtk_Form_Model_Instance_String extends Wtk_Form_Model_Instance
{
  function __construct ($path, $label, $value = '', $readonly = false)
  {
    parent::__construct ($path, $label, $value, $readonly);
  }

  function retrieve ($value)
  {
    if ($this->readonly)
      return true;

    $this->set($value);
    return TRUE;
  }
}
