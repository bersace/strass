<?php

class Strass_Pages_RendererInscription extends Wtk_Pages_Renderer_Form
{
  protected $view;

  function __construct($view, $model)
  {
    parent::__construct($model);
    $this->view = $view;
  }

  function renderFiche($group, $f)
  {
    $f->addParagraph()
      ->addFlags('warn')
      ->addInline("**Pas de pseudonyme ni d'abbréviations !**");

    $f->addEntry('fiche/prenom', 24);
    $f->addEntry('fiche/nom', 24);
    $f->addSelect('fiche/sexe', false);
    $f->addDate('fiche/naissance', '%e-%m-%Y');
  }

  function renderCompte($group, $f)
  {
    $f->addEntry('compte/adelec', 24);
    $f->addPassword('compte/motdepasse', 24);
    $f->addPassword('compte/confirmer', 24);
    $f->addParagraph()
      ->addFlags('info')
      ->addInline("Halte-là, on n'entre pas sur ce site sans présentez son CV scout ! :-)");
    $f->addEntry('presentation', 64, 8)->useLabel(false);
  }
}

$s = $this->document->addSection('inscription');
$s->addPages(null, $this->model,
	     new Strass_Pages_RendererInscription($this, $this->model->getFormModel()));
