<?php

class Strass_View_Helper_VignetteAlbum
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function vignetteAlbum($album,
				$label = null,
				$urlOptions = array())
  {
    if (!$album)
      return;

    $this->view->document->addStyleComponents('vignette');

    $urlOptions = array_merge(array('controller' => 'photos',
				    'action' => 'consulter',
				    'album' => $album->slug),
			      $urlOptions);
    $photo = $album->getPhotoAleatoire();
    $label = $label ? $label : $album->getIntituleCourt();
    $item = new Wtk_Container;
    $w = $item->addSection()
      ->addFlags('wrapper');
    if ($photo)
      $w->addImage($photo->getCheminVignette(), $photo->titre, $album->getIntituleComplet());
    else
       $w->addParagraph("Pas d'image !")->addFlags('empty', 'image');

    $item->addParagraph($label)->addFlags('label');
    $link = new Wtk_Link($this->view->url($urlOptions, true, true), $label, $item);
    $link->addFlags('vignette', 'album');
    return $link;
  }
}
