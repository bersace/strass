<?php

class Wtk_List_Item extends Wtk_Container
{
	function __construct($child = null, $ordered = false)
	{
		parent::__construct($child);
		$this->ordered = $ordered;
	}
}