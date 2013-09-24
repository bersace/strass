<?php

require_once 'Zend/Loader.php';

class Strass_Chargeur extends Zend_Loader
{
	public static function loadClass($class, $dirs = array()) {
		$dirs = array_merge($dirs, array(dirname(__FILE__)));
		parent::loadClass($class, $dirs);
	}

	public static function loadFile($filename, $dirs, $once = false)
	{
		$dirs = array_merge($dirs, array(dirname(__FILE__)));
		parent::loadFile($filename, $dirs, $once);
	}

	public static function isReadable($filename)
	{
		$paths = explode(':', get_include_path());
		$readable = is_readable($filename);
		foreach ($paths as $path) {
			$readable = $readable || is_readable($path.'/'.$filename);
		}

		return $readable;
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