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
		case 'Html5':
		  $f = Wtk_Render::factory(null, $format);
		  $media = array(null, 'all', 'screen', 'print', 'handheld');

		  foreach($components as $comp) {
		    foreach($media as $medium) {
		      $css = $this->basedir.'/'.$this->id.'/'.$f->template.'/'.$comp;
		      if ($medium)
			$css.= '.'.$medium;
		      $css.= '.css';
		      if (!file_exists($css))
			continue;

		      $files[] = array('file' => $css, 'medium' => $medium);
		    }
		  }
		  break;
		}

		return $files;
	}

	static function listAvailables($basedir = 'data/styles')
	{
	  $styles = array();
	  foreach(wtk_glob($basedir . '/*/metas.php') as $meta) {
	    $name = basename(dirname($meta));
	    array_push($styles, new self($name, $basedir));
	  }
	  return $styles;
	}
}
