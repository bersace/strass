<?php

class Strass_Views_PagesRenderer_UnitesInscrireAssistant extends Wtk_Pages_Renderer_Form
{
  protected $view;

  function __construct($view, $data)
  {
    parent::__construct();
    $this->view = $view;
    $this->data_annee = $data;
  }

  function renderInscription($g, $f)
  {
    extract($this->data_annee);
    if ($parente && $unite->isTerminale()) {
      $apps_model = $this->view->modelTableEffectifs($apps_parente);
      $f->addChild($this->view->tableEffectifs($parente, $apps_model, true, array()));
    }
    $apps_model = $this->view->modelTableEffectifs($apps);
    $f->addChild($this->view->tableEffectifs($unite, $apps_model, true, array()));

    $f->addSelect('inscription/individu');
    $f->addSelect('inscription/role', true);
    $f->addDate('inscription/debut', '%e-%m-%Y');
    $c = $f->addForm_Compound('Fin');
    $c->addCheck('inscription/clore')->useLabel(true);
    $c->addDate('inscription/fin', '%e-%m-%Y');
    $f->addCheck('inscription/continuer');
  }

  function renderFiche($g, $f)
  {
    $f->addEntry('fiche/prenom', 24);
    $f->addEntry('fiche/nom', 24);
    try {
      $f->addSelect('fiche/sexe');
    }
    catch (Exception $e) {}
    $f->addEntry('fiche/portable', 14);
    $f->addEntry('fiche/adelec', 14);
  }

  function renderCloture($g, $f)
  {
    extract($this->data_annee);
    $f->addSection('vignette')->addChild($this->view->vignetteIndividu($individu));
    $f->addChild($this->view->cvScout($cv));
    $f->addParagraph($individu->getFullname(). " est actif dans une autre unité.")
      ->addFlags('info');
    $c = $f->addForm_Compound('Fin');
    $c->addCheck('cloture/clore')->useLabel(true);
    $c->addDate('cloture/fin', '%e-%m-%Y');
  }
}

class Strass_Views_PagesRenderer_UnitesInscrireAnnee extends Strass_Views_PagesRenderer_Historique
{
  function render($annee, $data, $container)
  {
    extract($data);
    $container->addPages(null, $model,
			 new Strass_Views_PagesRenderer_UnitesInscrireAssistant($this->view, $data));
  }
}

$this->document->addPages(null, $this->model, new Strass_Views_PagesRenderer_UnitesInscrireAnnee($this));
