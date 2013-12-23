<?php

class Strass_Controller_Action_Exception_Internal extends Exception
{
	public	$titre;
	function __construct($titre, $message)
	{
		parent::__construct($message);
		$this->titre = $titre;
	}
	
}

