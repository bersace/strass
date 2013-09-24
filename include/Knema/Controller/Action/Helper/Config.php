<?php

class Knema_Controller_Action_Helper_Config extends Zend_Controller_Action_Helper_Abstract
{
	public function direct($name)
	{
		return new Knema_Config_Php($name);
	}
}