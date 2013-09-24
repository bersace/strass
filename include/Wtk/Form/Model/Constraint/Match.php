<?php

class Wtk_Form_Model_Constraint_Match extends Wtk_Form_Model_Constraint
{
  protected	$pattern;

  function __construct($instance, $pattern)
  {
    parent::__construct($instance);
    $this->pattern = $pattern;
  }

  function validate()
  {
    $this->instance->valid = (bool) preg_match($this->pattern, $this->instance->get());

    if (!$this->instance->valid) {
      throw new Wtk_Form_Model_Exception ("Le champ %s n'est pas valide.", $this->instance);
    }

    return $this->instance->valid;
  }
}