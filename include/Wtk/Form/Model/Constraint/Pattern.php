<?php

class Wtk_Form_Model_Constraint_Pattern extends Wtk_Form_Model_Constraint
{

  protected	$pattern;

  function __construct ($instance, $pattern)
  {
    parent::__construct ($instance);
    $this->pattern = $pattern;
  }

  function validate ()
  {
    // NOTE: on teste uniquement sur des charactère ascii.
    $this->instance->valid = (bool) preg_match ($this->pattern, strtoascii ($this->instance->get ()));

    if (!$this->instance->valid) {
      throw new Wtk_Form_Model_ConstraintException ("Le champ %s doit respecter le masque {{".$this->pattern."}}.", $this->instance);
    }
  }
}