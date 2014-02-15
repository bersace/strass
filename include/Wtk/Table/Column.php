<?php

class Wtk_Table_Column
{
	public	$title;
	public	$renderer;

	function __construct ($title, $renderer, $flags=null)
	{
		$this->title = $title;
		$this->renderer = $renderer;
		$this->flags = (array) $flags;
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