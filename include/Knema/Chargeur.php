<?php

require_once 'Zend/Loader.php';

class Knema_Chargeur extends Zend_Loader
{
	public static function loadClass($class, $dirs = array()) {
		$dirs = array_merge($dirs, array('include', dirname(__FILE__)));
		parent::loadClass($class, $dirs);
	}

	public static function autoload($class)
	{
		try {
			self::loadClass($class);
			return $class;
		} catch (Exception $e) {
			return false;
		}
	}
}