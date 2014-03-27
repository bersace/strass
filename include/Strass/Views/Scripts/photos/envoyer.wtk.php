<?php

class Strass_Views_PagesRenderer_PhotosEnvoyer extends Strass_Views_PagesRenderer_Historique
{
  function __construct($view)
  {
    parent::__construct($view);
    $this->href = $view->url(array('annee' => '%i'));
  }

  function render($annee, $data, $container)
  {
    extract($data);

    if ($photo)
      $container->addSection('vignette')->addChild($this->view->vignettePhoto($photo));

    $i = $form_model->getInstance('activite');
    if ($i->count() == 0) {
      $container->addParagraph("Aucun album en ".$annee)->addFlags('empty');
      return;
    }

    $f = $container->addForm($form_model);
    if ($i->count() > 1) {
      $f->addParagraph()
	->addFlags('info')
	->addInline("Sélectionnez l'activité durant laquelle la photo à été prise.");
      $f->addSelect('activite', true);
    }
    else {
      $f->addHidden('activite');
      $f->addParagraph()
	->addFlags('info')
	->addInline("Vous ajoutez une photo à l'album **".$activite."**.");
    }
    $f->addFile('photo');
    $f->addEntry('titre', 32);
    $f->addEntry('commentaire', 38, 8);
    try {
      $f->addCheck('envoyer');
    } catch (Exception $_) {}

    $b = $f->addForm_ButtonBox();
    $b->addForm_Submit($form_model->getSubmission('envoyer'));
  }
}

$this->document->addPages(null, $this->model, new Strass_Views_PagesRenderer_PhotosEnvoyer($this));
