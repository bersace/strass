<?php

class Wtk_Form_Model_Constraint_Required extends Wtk_Form_Model_Constraint
{
	protected $message = "Le champ %s est requis.";

	function _validate ()
	{
		return ! $this->instance->isEmpty();
	}
}