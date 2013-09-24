<?php

class Wtk_Image extends Wtk_Element
{
	function __construct ($url, $alt, $title, $width = NULL, $height = NULL)
	{
		parent::__construct ();
		$this->url		= $url;
		$this->alt		= $alt;
		$this->title		= $title instanceof Wtk_Metas ? $title->title : $title;
		$i = !$width || !$height ? (file_exists($url) ? getimagesize($url) : null) : null;
		$width = $width ? $width : $i[0];
		$height = $height ? $height : $i[1];
		$this->setSize ($width, $height);
	}

	function setSize ($width = NULL, $height = NULL)
	{
		$this->width	= $width;
		$this->height	= $height;
	}

	function useViewHelper($use = true)
	{
		$this->setDojoType("wtk.image.Photo");
	}
}