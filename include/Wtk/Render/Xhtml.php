<?php

require_once "Wtk/Utils/Xhtml.php";

class Wtk_Render_Xhtml extends Wtk_Render
{
	protected	$template =	'xhtml';
	protected	$mime = 	'application/xhtml+xml';

	function __construct($document)
	{
		parent::__construct($document);

		if (isset($_SERVER['HTTP_ACCEPT'])) {
			$accepts = explode(',', $_SERVER['HTTP_ACCEPT']);
			if (!in_array($this->mime, $accepts)) {
				$this->mime = 'text/html';
			} 
		}

		$this->mime = 'text/html';
	}
  
	function render()
	{
		$xhtml = parent::render();
		$xhtml = preg_replace("`([^\" ]+)@([^\" ]+)\.([^\" ]+)`",
				      "\$1&#64;\$2&#46;\$3", $xhtml);
		return $xhtml;
	}
}
