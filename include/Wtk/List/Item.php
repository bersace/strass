<?php

class Wtk_List_Item extends Wtk_Container
{
	function __construct($child = null, $ordered = false)
	{
		parent::__construct($child);
        unset($this->flags[0]);
        unset($this->flags[1]);
		$this->ordered = $ordered;
	}
}