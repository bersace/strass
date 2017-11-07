<?php
require_once 'Strass/Citation.php';

class Strass_Addon_Citation extends Strass_Addon
{
	function initView($view)
	{
		$t = new Citation;
		$view->citation = $t->findRandom();
	}
}