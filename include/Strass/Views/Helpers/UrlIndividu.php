<?php
class Strass_View_Helper_UrlIndividu
{
	protected	$view;

	public function setView($view)
	{
		$this->view = $view;
	}

	function urlIndividu($individu, $action = 'fiche', $controller = 'individus',
			     $reset = false, $prefix = false)
	{
		return $this->view->url(array('controller'	=> $controller,
					      'action'		=> $action,
					      'individu'	=> $individu->slug),
					$reset, $prefix);

	}
}