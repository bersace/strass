<?php

class Strass_Controller_Plugin_Alias extends Zend_Controller_Plugin_Abstract
{
	function preDispatch()
	{
		$config = new Strass_Config_Php('site');
		$alias = $config->aliases->toArray();
		$request = $this->getRequest();
		$controller = $request->getControllerName();
		if (array_key_exists($controller, $alias)) {
			foreach($alias[$controller] as $key => $value) {
				$request->setParam($key, $value);
			}
		}
	}
}