<?php

class Wtk_Dialog extends Wtk_Container
{
	protected $stylecomponent = 'dialog';

	function __construct ($title = NULL)
	{
		parent::__construct ();
		$this->data['title'] = $title;
	}

	function template ()
	{
		$tpl = $this->elementTemplate ();
		$tpl->addChild ('content', $this->containerTemplate ());
		return $tpl;
	}
}