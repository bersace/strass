<?php
require_once 'Strass/Citation.php';

class Strass_Addon_Citation extends Knema_Addon
{
	protected $citation;

	function __construct()
	{
		$tc = new Citation();
		$this->citation = $tc->fetchAll(null, 'RANDOM()', 1)->current();
	}

	function initView($view)
	{
		$view->document->addStyleComponents('citation');
		$view->citation = $this->citation;
	}
}