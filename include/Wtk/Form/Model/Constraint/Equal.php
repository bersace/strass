<?php

class Wtk_Form_Model_Constraint_Equal extends Wtk_Form_Model_Constraint
{
  protected	$ref;
  protected	$silent;

  function __construct($instance, $ref, $silent = false)
  {
    parent::__construct($instance);
    $this->ref = $ref;
    $this->silent = $silent;
  }

  function validate()
  {
    $val = $this->ref instanceof Wtk_Form_Model_Instance ? $this->ref->get() : $this->ref;
    $this->instance->valid = $this->instance->get() == $val;

    if (!$this->instance->valid && !$this->silent) {
      throw new Wtk_Form_Model_Exception ("Le champ %s doit être égal à ".$val.".", $this->instance);
    }

    return $this->instance->valid;
  }
}