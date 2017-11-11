<?php

class Wtk_Raw extends Wtk_Element
{
	function __construct ($raw = '')
	{
		parent::__construct ();
		$this->raw = $raw;
	}
}
