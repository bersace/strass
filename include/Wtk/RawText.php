<?php

class Wtk_RawText extends Wtk_Element
{
	function __construct ($text = '')
	{
		parent::__construct ();
		$this->text = $text;
	}

	function setText ($text)
	{
		$this->text = $text;
	}

	function __toString()
	{
		return $this->text;
	}
}