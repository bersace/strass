<?php

abstract class Wtk_Form_Model_Instance
{
  public	$id;
  public	$path;
  public	$label;
  public	$value;
  public	$valid;
  public $readonly;

  function __construct ($path, $label = null, $value = NULL)
  {
    $this->label	= $label;
    $this->valid	= NULL;
    $this->readonly = false;
    $this->set($value);
    $this->setPath ($path);
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
}
