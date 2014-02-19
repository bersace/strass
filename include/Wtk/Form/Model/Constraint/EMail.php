<?php

class Wtk_Form_Model_Constraint_EMail extends Wtk_Form_Model_Constraint_Match
{
  function __construct($instance) {
    parent::__construct($instance,
			'/^[[:alnum:]\._+-]{3,}@[[:alnum:]\._-]{3,}(\.[[:alnum:]]{2,6})*$/');
  }
}
