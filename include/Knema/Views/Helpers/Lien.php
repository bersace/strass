<?php

class Knema_View_Helper_Lien
{
	protected	$view;

	public function setView($view)
	{
		$this->view = $view;
	}

	public function lien($urlOptions = array(), $label = null, $reset = false)
	{
		return new Wtk_Link($this->view->url($urlOptions, $reset),
				    $label);
	}
}
