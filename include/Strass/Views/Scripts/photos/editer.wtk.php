<?php

class Strass_Views_PagesRenderer_PhotosEditer extends Strass_Views_PagesRenderer_Historique
{
  function __construct($view)
  {
    parent::__construct($view);
    $this->href = $view->url(array('controller' => 'photos',
				   'action' => 'editer',
				   'photo' => $view->photo->slug,
				   'annee' => '%i'),
			     true);
  }

  function render($annee, $data, $container)
  {
    extract($data);

    $f = $container->addForm($form_model);
    $v = $f->addSection('vignette')
      ->addChild($this->view->vignettePhoto($photo));
    $f->addFile('photo');
    $f->addEntry('titre', 32);
    try {
      $i = $form_model->getInstance('activite');
      if ($i->count() > 1) {
	$f->addSelect('activite', true);
      }
      else {
	$f->addHidden('activite');
	$f->addParagraph()
	  ->addFlags('info')
	  ->addInline("Vous envoyez une photo pour **".$activite."**.");
      }
    }
    catch (Exception $e) {
	$f->addParagraph()
	  ->addFlags('info')
	  ->addInline("Pas d'activité pour cette année");
    }

    $b = $f->addForm_ButtonBox();
    $b->addForm_Submit($form_model->getSubmission('enregistrer'));
  }
}

$this->document->addPages(null, $this->model, new Strass_Views_PagesRenderer_PhotosEditer($this));
