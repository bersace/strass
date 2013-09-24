<?php

class Wtk_Table_Column
{
	public	$title;
	public	$renderer;

	function __construct ($title, $renderer)
	{
		$this->title = $title;
		$this->renderer = $renderer;
	}

	function getRenderer()
	{
		return $this->renderer;
	}

	function getTitle ()
	{
		return $this->title;
	}
}