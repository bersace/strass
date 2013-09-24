<?php

class Wtk_Form_Model_Instance_String extends Wtk_Form_Model_Instance
{
  function __construct ($path, $label, $value = '')
  {
    parent::__construct ($path, $label, $value);
  }

  function retrieve ($value)
  {
    $this->set(get_magic_quotes_gpc() ? stripslashes($value) : $value);
    return TRUE;
  }
}

?>