<?php

class Strass_Views_PagesRenderer_PhotosEnvoyer extends Strass_Views_PagesRenderer_Historique
{
  function __construct($view)
  {
    parent::__construct($view);
    $this->href = $view->url(array('controller' => 'photos',
				   'action' => 'envoyer',
				   'unite' => $view->unite->slug,
				   'annee' => '%i'),
			     true);
  }

  function render($annee, $data, $container)
  {
    if (array_key_exists('model', $data))
      return $this->renderForm($annee, $data, $container);
    else
      return $this->renderAlbums($annee, $data, $container);
  }

  function renderAlbums($annee, $data, $container)
  {
    extract($data);
    $container->addDialog("Choisissez un album")->addFlags('info');
    $l = $container->addList()->addFlags('albums', 'vignettes');
    if ($activites->count()) {
      foreach ($activites as $album) {
	$l->addItem($this->view->vignetteAlbum($album, null,
					       array('action' => 'envoyer')));
      }
    }
    else
      $container->addParagraph("Pas de photos de l'année ".$annee." !")->addFlags('empty');

    return $container;
  }

  function renderForm($annee, $data, $container)
  {
    extract($data);

    if ($photos->count()) {
      $l = $container->addList();
      $l->addFlags('vignettes', 'photos');
      foreach($photos as $photo) {
	$i = $l->addItem($this->view->vignettePhoto($photo));
	$i->addFlags('vignette');
      }
    }
    else
      $container->addParagraph("Pas de photos")->addFlags('empty');

    $f = $container->addForm($model);
    $g = $f->addForm_Fieldset("Nouvelle photo");
    $g->addFile('photo');
    $g->addEntry('titre', 32);
    $g->addEntry('commentaire', 52, 8);
    $f->addCheck('envoyer');

    $b = $f->addForm_ButtonBox();
    $b->addForm_Submit($model->getSubmission('envoyer'));
  }
}

$this->document->addPages(null, $this->model, new Strass_Views_PagesRenderer_PhotosEnvoyer($this));
