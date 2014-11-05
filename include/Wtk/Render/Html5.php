<?php

require_once "Wtk/Utils/Xhtml.php";

class Wtk_Render_Html5 extends Wtk_Render
{
	public	$template =	'html';
	protected	$mime = 	'text/html';

	function render()
	{
		$html = parent::render();
		$html = preg_replace("`([^\" ]+)@([^\" ]+)\.([^\" ]+)`",
				     "\$1&#64;\$2&#46;\$3", $html);
		return $html;
	}
}
