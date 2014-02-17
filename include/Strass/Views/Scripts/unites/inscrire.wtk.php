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

  function renderApp($g, $f)
  {
    $f->addSelect('app/role', true);
    $f->addDate('app/debut');
    $f->addCheck('app/clore');
    $f->addDate('app/fin');
  }
}

$this->document->addPages(null, $this->model, new Strass_Pages_Renderer_UnitesInscrire($this));
