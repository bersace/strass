<?php
require_once 'Strass/Citation.php';

class Strass_Addon_Citation extends Strass_Addon
{
	function initView($view)
	{
		$view->document->addStyleComponents('citation');
		$t = new Citation;
		$view->citation = $t->findRandom();
	}
}