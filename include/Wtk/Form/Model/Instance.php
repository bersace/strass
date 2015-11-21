<?php

abstract class Wtk_Form_Model_Instance
{
  public	$id;
  public	$path;
  public	$label;
  public	$value;
  public	$valid;
  public $errors = array();
  public $readonly;

  function __construct ($path, $label = null, $value = NULL, $readonly = false)
  {
    $this->label	= $label;
    $this->valid	= true;
    $this->setPath ($path);
    $this->set($value);
    $this->setReadonly($readonly);
  }

  function setReadonly($ro = true)
  {
    $this->readonly = $ro;
    return $this;
  }

  function setPath ($path)
  {
    $this->path		= $path;
    $this->id		= basename ($this->path);
  }

  function retrieve ($value)
  {
    if (!$this->readonly)
      $this->set($value);
    return TRUE;
  }


  function set ($value)
  {
    $this->value = $value;
  }

  function get ()
  {
    return $this->value;
  }

  function isEmpty()
  {
	return !$this->get();
  }

  function __toString()
  {
    return (string) $this->value;
  }
}
