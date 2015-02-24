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
      $this->basedir = Strass::getRoot().'private/config/';
    }

    $this->filename = $this->basedir.$name.'.php';

    if ($config === null) {
      if (file_exists($this->filename)) {
	/* Ne pas utiliser include, car le cache d'include fausse
	   certains cas, notamment à l'installation. */
	$php = str_replace('<?php', '', file_get_contents($this->filename));
	$config = eval($php);
      }

      if (!is_array($config))
	$config = array();
    }

    parent::__construct ($config, true);
  }

  public function write()
  {
    $dirname = dirname ($this->filename);
    $dirs = explode ('/', $dirname);
    $dir = '';
    foreach ($dirs as $d) {
      $dir.= $d.DIRECTORY_SEPARATOR;
      if (!file_exists($dir)) {
	mkdir ($dir);
      }
    }

    file_put_contents($this->filename,
		      "<?php\n".
		      "return ".var_export($this->toArray(), true).";\n");
  }

  public function get($name, $default=null)
  {
    $parts = explode('/', $name);
    $leaf = array_pop($parts);
    $node = $this;

    foreach($parts as $part) {
      $node = parent::get($part, new self(null, array()));
    }

    if ($node === $this)
      return parent::get($leaf, $default);
    else
      return $node->get($leaf, $default);
  }
}
