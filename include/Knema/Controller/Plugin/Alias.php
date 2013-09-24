<?php

class Knema_Controller_Plugin_Alias extends Zend_Controller_Plugin_Abstract
{
	function preDispatch()
	{
		$config = new Knema_Config_Php('knema/url/alias');
		$alias = $config->toArray();
		$request = $this->getRequest();
		$controller = $request->getControllerName();
		if (array_key_exists($controller, $alias)) {
			foreach($alias[$controller] as $key => $value) {
				$request->setParam($key, $value);
			}
		}
	}
}