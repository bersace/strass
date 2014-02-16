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
			$media = array('all', 'screen', 'print', 'handheld');
			foreach($media as $medium) {
				foreach($components as $comp) {
					$bf = $this->basedir.'/'.$this->id.'/xhtml'.
						'/'.$comp.'.'.$medium;
					$fs = wtk_glob($bf.'.*.css');
					rsort($fs);

					if (file_exists($bf.'.css'))
						array_unshift($fs, $bf.'.css');

					foreach($fs as $f)
						$files[] = array('file' => $f,
								 'medium' => $medium);
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
