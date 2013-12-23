<?php

class Strass_View_Helper_Url extends Zend_View_Helper_Url	
{
	function url(array $urlOptions = array(), $reset = false, $prefix = false)
	{
		$url = parent::url($urlOptions, null, $reset);
		if ($prefix)
			$url = 'http://'.$_SERVER['HTTP_HOST'].$url;

		return $url;
	}
}
