<?php

class Scout_Pages_Renderer_Albums extends Strass_Views_PagesRenderer_Historique
{
  function render($annee = NULL, $data, $parent)
  {
    extract($data);
    $l = $parent->addList()->addFlags('albums', 'vignettes');
    if ($activites->count()) {
      foreach ($activites as $activite) {
	$v = $this->view->vignettePhoto($activite->getPhotoAleatoire(),
					$activite->getIntitule(false, true, true),
					array('action'		=> 'consulter',
					      'controller'	=> 'photos',
					      'activite'	=> $activite->slug,
					      'photo'		=> null),
					true);
	$l->addItem($v)->addFlags('vignette');
      }
    }
    else {
      $parent->addParagraph("Pas de photos de l'année ".$annee." !")->addFlags('empty');
    }
  }
}

$this->document->addStyleComponents('vignette');

$s = $this->document->addPages('albums', $this->model, new Scout_Pages_Renderer_Albums($this));
