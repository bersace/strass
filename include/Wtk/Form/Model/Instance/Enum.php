<?php

class Wtk_Form_Model_Instance_Enum extends Wtk_Form_Model_Instance implements Iterator, Countable
{
  protected	$enum;
  protected	$multiple;

  /**
   *
   * @param string	Pathname
   * @param string	Descriptive label
   * @param string	Default value
   * @param array	Values => Label array
   * @param boolean	Wether to allow multiple selection
   */
  function __construct ($path, $label, $value, $enum, $multiple = FALSE)
  {
    parent::__construct ($path, $label, $value);
    $this->enum = $enum;
    $this->multiple = $multiple;
  }

  function addItems ($value)
  {
    $values = func_get_args ();
    $this->enum = array_merge ($this->enum, $values);
    $this->enum = array_uniq ($this->enum);
  }

  function getItems ()
  {
    return $this->enum;
  }

  function getMultiple()
  {
    return $this->multiple;
  }

  function retrieve ($value)
  {
    if ($value == 'NULL' || is_null($value)) {
      $this->value = $this->getMultiple() ? array() : NULL;
    }
    else if (is_array($value)) {
      $this->value = $value;
    }
    else if (array_key_exists ($value, $this->enum)) {
      $this->value = $value;
    }
    
    if ($this->multiple) {
        $this->value = (array) $value;
    }
    
    return TRUE;
  }


  // ITERATOR

  function count ()
  {
    return count ($this->enum);
  }

  public function rewind() {
    return reset($this->enum);
  }

  public function current() {
    return current($this->enum);
  }

  public function key() {
    return key($this->enum);
  }

  public function next() {
    return next($this->enum);
  }

  public function valid() {
    return $this->current() !== false;
  }
}

?>
