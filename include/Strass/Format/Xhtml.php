<?php

class Strass_Format_Xhtml extends Strass_Format_Wtk
{
	protected	$_suffix = 'xhtml';
	protected	$_mimeType = 'application/xhtml+xml';
	protected	$_title = 'XHTML';
	protected	$_wtkRender = 'Xhtml';
	protected	$_renderAddons = true;

	function __construct()
	{
		$this->_mimeType = 'text/html';

// 		if (isset($_SERVER['HTTP_ACCEPT'])
// 		    && strpos($_SERVER['HTTP_ACCEPT'], $this->_mimeType) === false)
// 			$this->mimeType = 'text/html';
	}
}