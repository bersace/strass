<?php

class Wtk_Table_Model_Array extends Wtk_Table_Model
{
  function __construct($array)
  {
    $cols = array_keys($array[0]);
    parent::__construct($cols);
    foreach($array as $tuple) {
      call_user_func_array(array($this, 'append'), $tuple);
    }
  }
}