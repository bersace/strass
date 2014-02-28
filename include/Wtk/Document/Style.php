<?php

class Wtk_Document_Style {
	public $id;
	public $metas;
	protected $basedir;
	protected $mail;

	function __construct($id = 'default', $basedir = 'data/styles') {
		$this->id = $id;
		$this->basedir = $basedir;
		$this->metas = include $basedir.'/'.$id.'/metas.php';
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
		return $this->basedir.'/'.$this->id.'/favicon.png';
	}

	/*
	 * liste les fichiers externe à embarqué.
	 */
	function getFiles(array $components, $format = 'Xhtml') {
		$files = array();

		switch ($format) {
		case 'Xhtml':
		  $media = array(null, 'all', 'screen', 'print', 'handheld');
		  foreach($components as $comp) {
		    foreach($media as $medium) {
		      $bf = $this->basedir.'/'.$this->id.'/xhtml'.
			'/'.$comp;
		      if ($medium) {
			$bf.= '.'.$medium;
			$fs = wtk_glob($bf.'.*.css');
		      }
		      else {
			$fs = wtk_glob($bf.'.css');
		      }

		      if (file_exists($bf.'.css'))
			array_unshift($fs, $bf.'.css');

		      $fs = array_unique($fs);
		      rsort($fs);

		      foreach($fs as $f)
			$files[] = array('file' => $f,
					 'medium' => $medium ? $medium : 'all');
		    }
		  }
		  break;
		}

		return $files;
	}

	static function listAvailables($basedir = 'data/styles')
	{
	  $styles = array();
	  foreach(glob($basedir . '/*/metas.php') as $meta) {
	    $name = basename(dirname($meta));
	    array_push($styles, new self($name, $basedir));
	  }
	  return $styles;
	}
  }
