<?php

class Wtk_Form_Control_Entry extends Wtk_Form_Control
{
	function __construct ($instance, $width = 12, $height = 1, $suffix = '')
	{
		parent::__construct ($instance);
		$this->setSize ($width, $height);
		$this->useSuffix($suffix);
	}

	function useSuffix($suffix)
	{
		$this->suffix = $suffix;
	}

	function setSize ($width, $height)
	{
		$this->width = is_null ($width) ? $this->width : $width;
		$this->height = is_null ($height) ? $this->height : $height;
	}
}
