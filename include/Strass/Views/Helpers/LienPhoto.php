<?php

class Strass_View_Helper_LienPhoto
{
	protected	$view;

	public function setView($view)
	{
		$this->view = $view;
	}

	public function lienPhoto($photo,
				  $label = null,
				  $action = 'voir',
				  $controller = 'photos',
				  $urlOptions = array(),
				  $reset = true)
	{
		if (!$photo) {
			return;
		}
		$label = $label ? $label : ucfirst($photo->titre);
		return new Wtk_Link($this->view->url(array_merge($urlOptions,
								 array('controller' => $controller,
								       'action' => $action,
								       'activite' => $photo->activite,
								       'photo' => $photo->id)),
						     null,
						     true),
				    new Wtk_Metas(array('title' => $photo->titre)),
				    new Wtk_RawText($label));
	}
}
