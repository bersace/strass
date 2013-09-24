<?php

class Wtk_Form_Model_Constraint_Length extends Wtk_Form_Model_Constraint
{
  protected	$min;
  protected	$max;

  function __construct($instance, $min = null, $max = null)
  {
    parent::__construct($instance);
    $this->min = $min;
    $this->max = $max;
  }

  function validate()
  {
    $len = strlen($this->instance->get());
    $this->instance->valid = (is_null($this->min) || $this->min <= $len)
      && (is_null($this->max) || $this->max >= $len);

    if (!$this->instance->valid) {
      $conds = array();
      $ks = array('min' => 'sup',
		 'max' => 'inf');
      foreach($ks as $k => $p) {
	if (!is_null($this->$k)) {
	  $conds[] = $p.'érieure ou égale à '.$this->$k;
	}
      }
	
      throw new Wtk_Form_Model_Exception ("La longueur du champ %s doit être ".implode(' et ', $conds).".", $this->instance);
    }

    return $this->instance->valid;
  }
}