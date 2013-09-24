<?php

class Wtk_Paragraph extends Wtk_Container
{
	function __construct($text = '')
	{
		parent::__construct();
		$children = func_get_args();
		call_user_func_array(array(&$this, 'addChildren'),
				     $children);
	}
}
