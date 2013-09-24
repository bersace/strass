<?php

class Wtk_Form_Model_Constraint_Forbid extends Wtk_Form_Model_Constraint
{
	protected	$forbidden; // liste des valeur interdite

	function __construct($instance, $forbidden, $message = "La valeur donnée pour %s est interdite.")
	{
		parent::__construct($instance, $message);
		$this->forbidden = $forbidden;
	}

	function _validate($value)
	{
		return !in_array($value, $this->forbidden);
	}
}