<?php
require_once 'Wtk/Utils.php';

abstract class Wtk
{
	public static function init()
	{
		spl_autoload_register(array('Wtk', 'autoload'));
	}

	public static function autoload ($class)
	{
		$file = str_replace ('_', '/', $class).'.php';
		$exists = false;

		if (file_exists('include/'.$file))
			include_once $file;
	}
}
