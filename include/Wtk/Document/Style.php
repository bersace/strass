<?php

class Wtk_Document_Style {
  static public $path;
	public $id;
	public $metas;
	protected $basedir;

	static function factory($id)
	{
	  foreach (self::$path as $path) {
	    if (file_exists($path . $id)) {
	      return new self($id, $path);
	    }
	  }
	  throw new Exception("Style ".$id." introuvable");
	}

	static function listAvailables()
	{
	  $styles = array();
	  foreach(self::$path as $basedir) {
	    if (!file_exists($basedir))
	      continue;

	    foreach(wtk_glob($basedir . '*/metas.php') as $meta) {
	      $name = basename(dirname($meta));
	      array_push($styles, new self($name, $basedir));
	    }
	  }
	  return $styles;
	}

	function __construct($id = 'default', $basedir = 'static/styles/') {
		$this->id = $id;
		$this->basedir = $basedir . $id . DIRECTORY_SEPARATOR;
		$this->metas = include $this->basedir . 'metas.php';

		if ($this->basedir[0] == DIRECTORY_SEPARATOR) {
		  $parent = dirname(dirname($basedir));
		  $this->baseurl = substr($this->basedir, strlen($parent)+1);
		}
		else
		  $this->baseurl = $this->basedir;
	}

	function __get($name)
	{
		return $this->metas->$name;
	}

	function __toString()
	{
		return $this->id;
	}

	function getFavicon()
	{
		return $this->basedir.'/favicon.png';
	}

	/*
	 * liste les fichiers externe à embarqué.
	 */
	function getFiles(array $components, $format = 'Xhtml') {
		$files = array();

		switch ($format) {
		case 'Xhtml':
		case 'Html5':
		  $f = Wtk_Render::factory(null, $format);
		  $media = array(null, 'all', 'screen', 'print', 'handheld');

		  foreach($components as $comp) {
		    foreach($media as $medium) {
		      $css = $f->template.'/'.$comp;
		      if ($medium)
			$css.= '.'.$medium;
		      $css.= '.css';
		      if (!file_exists($this->basedir . $css))
			continue;

		      $files[] = array('file' => $this->baseurl . $css,
				       'medium' => $medium);
		    }
		  }
		  break;
		}

		return $files;
	}
}
