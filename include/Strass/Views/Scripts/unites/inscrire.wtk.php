<?php

class Strass_Pages_Renderer_UnitesInscrire extends Wtk_Pages_Renderer_Form
{
  protected $view;

  function __construct($view)
  {
    parent::__construct();
    $this->view = $view;
  }

  function renderIndividu($g, $f)
  {
    $f->addSelect('individu/individu');
  }

  function renderFiche($g, $f)
  {
    $f->addParagraph('fiche')->addFlags('empty');
  }

  function renderRole($g, $f)
  {
    $f->addParagraph('rôle')->addFlags('empty');
  }
}

$this->document->addPages(null, $this->model, new Strass_Pages_Renderer_UnitesInscrire($this));
