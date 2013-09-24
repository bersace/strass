<?php

class Wtk_Form_Control_Spin extends Wtk_Form_Control_Entry
{
	function __construct ($instance, $suffix = '')
	{
		parent::__construct ($instance, 3, 1, $suffix);
	}

	function elementTemplate($class = 'Wtk_Form_Control_Entry')
	{
		return parent::elementTemplate($class);
	}
}
