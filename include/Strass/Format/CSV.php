<?php

class Strass_Format_CSV extends Strass_Format
{
	protected	$_suffix	= 'csv';
	protected	$_viewSuffix	= 'csv';
	protected	$_mimeType	= 'text/csv';
	protected	$_title		= 'CSV';
	protected	$_renderFooter	= false;
	protected	$_download	= true;

	function _render($view)
	{
		return $this->_output;
	}
}