<?php

class Strass_Pages_Renderer_UnitesInscrire extends Wtk_Pages_Renderer_Form
{
  protected $view;

  function __construct($view)
  {
    parent::__construct();
    $this->view = $view;
  }

  function renderInscription($g, $f)
  {
    $f->addSelect('inscription/individu');
    $f->addSelect('inscription/role', true);
    $f->addDate('inscription/debut');
    $c = $f->addForm_Compound();
    $c->addCheck('inscription/clore')->useLabel(true);
    $c->addDate('inscription/fin', '%e-%m-%Y');
  }

  function renderFiche($g, $f)
  {
    $f->addEntry('fiche/prenom', 24);
    $f->addEntry('fiche/nom', 24);
    try {
      $f->addSelect('fiche/sexe');
    }
    catch (Exception $e) {}
    $f->addDate('fiche/naissance');
  }
}

$this->document->addChild($this->tableEffectifs($this->unite, $this->modelTableEffectifs($this->apps),
						true, array()));
$this->document->addPages(null, $this->model, new Strass_Pages_Renderer_UnitesInscrire($this));
