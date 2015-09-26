<?php

class Strass_View_Helper_Url extends Zend_View_Helper_Url
{
	function url(array $urlOptions = array(), $reset = false, $prefix = false)
	{
		$url = parent::url($urlOptions, null, $reset);
		if ($prefix)
			$url = 'http://'.$_SERVER['HTTP_HOST'].$url;

        if (!$reset && isset($_SERVER['QUERY_STRING']))
            $url.= '?' . $_SERVER['QUERY_STRING'];

		return $url;
	}
}
