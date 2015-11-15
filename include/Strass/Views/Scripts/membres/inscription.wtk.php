<?php

class Strass_Pages_RendererInscription extends Wtk_Pages_Renderer_Form
{
  protected $view;

  function __construct($view)
  {
    parent::__construct();
    $this->view = $view;
  }

  function renderFiche($group, $f)
  {
    $f->addParagraph()
      ->addFlags('warn')
      ->addInline("**Pas de pseudonyme ni d'abbréviations !**");

    $f->addEntry('fiche/prenom', 24);
    $f->addEntry('fiche/nom', 24);
    try {
      $f->addSelect('fiche/sexe', false);
    }
    catch (Exception $e) {
      $f->addHidden('fiche/sexe');
    }
    $f->addDate('fiche/naissance', '%e-%m-%Y');
  }

  function renderCompte($group, $f)
  {
    $f->addEMail('compte/adelec', 24);
    $f->addPassword('compte/motdepasse', 24);
    $f->addPassword('compte/confirmer', 24);
    $f->addParagraph()
      ->addFlags('info')
      ->addInline("Halte-là, on n'entre pas sur ce site sans présentez son CV scout ! :-)");
    $f->addEntry('presentation', 64, 8)->useLabel(false);
  }
}

$this->document->addPages(null, $this->model, new Strass_Pages_RendererInscription($this));
