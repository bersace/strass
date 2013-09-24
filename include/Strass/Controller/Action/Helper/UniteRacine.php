<?php

class Strass_Controller_Action_Helper_UniteRacine extends Zend_Controller_Action_Helper_Abstract
{
	function direct()
	{
		$unites = new Unites();
		return $unites->fetchAll('unites.parent IS NULL')->current();
	}
}
