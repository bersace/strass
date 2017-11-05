<?php

class Wtk_Paragraph extends Wtk_Container
{
	function __construct($text = '')
	{
		parent::__construct();
        unset($this->flags[0]);
		$children = func_get_args();
		call_user_func_array(array(&$this, 'addChildren'),
				     $children);
	}
}
