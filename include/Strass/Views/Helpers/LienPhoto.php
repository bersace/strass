<?php

class Strass_View_Helper_LienPhoto
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function lienPhoto($photo)
  {
    if (!$photo)
      return;

    return new Wtk_Link($this->view->url(array('controller' => 'photos',
					       'action' => 'voir',
					       'photo' => $photo->slug),
					 true),
			ucfirst($photo->titre), ucfirst($photo->titre));
  }
}
