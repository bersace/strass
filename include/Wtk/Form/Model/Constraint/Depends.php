<?php

class Wtk_Form_Model_Constraint_Depends extends Wtk_Form_Model_Constraint
{
  protected	$ref;

  function __construct($instance, $ref)
  {
    parent::__construct($instance);
    $this->ref = $ref;
  }

  function getFlags()
  {
    return array($this->getFlag(), 'depends-'.wtk_strtoid($this->ref->path));
  }

  function getScripts()
  {
    return array($this->getScript());
  }

  function validate ()
  {
    return true;
  }
}