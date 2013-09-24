<?php

class Wtk_Form_Model_Instance_Color extends Wtk_Form_Model_Instance
{
  function getCSS()
  {
    return '#'.$this->getHex();
  }

  function getHex()
  {
    return sprintf("%02X%02X%02X",
		   $this->value[0],
		   $this->value[1],
		   $this->value[2]);
  }

  function retrieve($value)
  {
    preg_match("/#?([0-9a-fA-F]{1,2}){3}/", $value, $res);
    Orror::kill($value, $res);
  }
}