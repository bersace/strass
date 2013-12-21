<?php

class Scout_Pages_Renderer_Albums extends Strass_Views_PagesRenderer_Historique
{
  function render($annee = NULL, $data, $parent)
  {
    extract($data);
    $l = $parent->addList()->addFlags('albums');
    foreach ($activites as $activite) {
      $v = $this->view->vignettePhoto($activite->getPhotoAleatoire(),
				      wtk_ucfirst($activite->getIntitule(false)),
				      array('action'		=> 'consulter',
					    'controller'	=> 'photos',
					    'activite'	=> $activite->id,
					    'photo'		=> null),
				      true);
      $l->addItem($v)->addFlags('vignette');
    }
  }
}

$this->document->addStyleComponents('vignette');

$s = $this->content->addPages('albums', $this->model, new Scout_Pages_Renderer_Albums($this));

