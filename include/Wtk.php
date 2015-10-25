<?php
require_once 'Wtk/Utils.php';

/* C'est sale, car Wtk ne cherche que dans son dossier include. En même temps
 * voilà quoi. */
define('WTK_INCLUDE_DIR', dirname(__FILE__));

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


		if (file_exists(WTK_INCLUDE_DIR . DIRECTORY_SEPARATOR . $file))
			include_once $file;
	}
}
