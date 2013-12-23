<?php

require_once 'Zend/Config.php';
require_once 'Zend/Registry.php';

class Strass_Config_Php extends Zend_Config
{
	protected	$basedir;
	protected	$name;
	public	$filename;
	protected	$config;

	public function __construct ($name, $config = null)
	{
		$this->name = $name;
		$this->config = $config;

		try {
			$this->basedir = Zend_Registry::get('config_basedir');
		}
		catch (Zend_Exception $e) {
			$this->basedir = 'private/config/';
		}

		$this->filename = $this->basedir.$name.'.php';

		if ($config === null) {
			$config = include $this->filename;
		}

		parent::__construct ($config, true);
	}

	public function write()
	{
		$dirname = dirname ($this->filename);
		$dirs = explode ('/', $dirname);
		$dir = '';
		foreach ($dirs as $d) {
			$dir.= $d.'/';
			if (!file_exists($dir)) {
				mkdir ($dir);
			}
		}
    
		file_put_contents ($this->filename,
				   "<?php\n".
				   "return ".var_export ($this->toArray(), true).";\n");
	}
}