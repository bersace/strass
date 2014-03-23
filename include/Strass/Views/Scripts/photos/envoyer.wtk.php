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
    extract($data);

    $f = $container->addForm($form_model);
    $i = $form_model->getInstance('activite');
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
	->addInline("Vous envoyez une photo pour **".$activite."**.");
    }
    $f->addFile('photo');
    $f->addEntry('titre', 32);
    $f->addEntry('commentaire', 38, 8);
    $f->addCheck('envoyer');

    $b = $f->addForm_ButtonBox();
    $b->addForm_Submit($form_model->getSubmission('envoyer'));
  }
}

$this->document->addPages(null, $this->model, new Strass_Views_PagesRenderer_PhotosEnvoyer($this));
