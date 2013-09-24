<?php

class Wtk_Form_Button extends Wtk_Container
{
	function __construct ($label)
	{
		parent::__construct ();
		$this->addChild($label);
	}
}