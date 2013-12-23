<?php

class Strass_Controller_Action_Helper_Config extends Zend_Controller_Action_Helper_Abstract
{
	public function direct($name)
	{
		return new Strass_Config_Php($name);
	}
}