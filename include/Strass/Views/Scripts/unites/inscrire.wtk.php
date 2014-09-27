<?php

class Strass_Views_PagesRenderer_UnitesInscrireAssistant extends Wtk_Pages_Renderer_Form
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
    $f->addDate('fiche/naissance');
  }
}

class Strass_Views_PagesRenderer_UnitesInscrireAnnee extends Strass_Views_PagesRenderer_Historique
{
  function render($annee, $data, $container)
  {
    extract($data);
    $form_model = $model;
    if ($parente && $unite->isTerminale()) {
      $apps_model = $this->view->modelTableEffectifs($apps_parente);
      $container->addChild($this->view->tableEffectifs($parente, $apps_model, true, array()));
    }
    $apps_model = $this->view->modelTableEffectifs($apps);
    $container->addChild($this->view->tableEffectifs($unite, $apps_model, true, array()));
    $container->addPages(null, $model, new Strass_Views_PagesRenderer_UnitesInscrireAssistant($this->view));
  }
}

$this->document->addPages(null, $this->model, new Strass_Views_PagesRenderer_UnitesInscrireAnnee($this));
