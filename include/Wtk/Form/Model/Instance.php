<?php

abstract class Wtk_Form_Model_Instance
{
  public	$id;
  public	$path;
  public	$label;
  protected	$value;
  public	$valid;

  function __construct ($path, $label = null, $value = NULL)
  {
    $this->label	= $label;
    $this->valid	= NULL;
    $this->set($value);
    $this->setPath ($path);
  }

  function setPath ($path)
  {
    $this->path		= $path;
    $this->id		= basename ($this->path);
  }

  function retrieve ($value)
  {
    $this->set ($value);
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

?>