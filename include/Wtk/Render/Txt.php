<?php
require_once "Wtk/Utils/Txt.php";

class Wtk_Render_Txt extends Wtk_Render
{
	public	$template	= 'txt';
	protected	$mime		= 'text/plain';
}