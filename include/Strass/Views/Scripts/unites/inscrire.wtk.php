<?php

class IndividuTemporaire implements Zend_Acl_Resource_Interface
{
    function __construct($data)
    {
        $this->data = $data;
        $this->slug = null;
    }

    function __toString()
    {
        return $this->getFullName();
    }

    function getResourceId()
    {
        return 'visiteur';
    }

    function getFullname()
    {
        return $this->data['fiche']['prenom'] . ' ' . $this->data['fiche']['nom'];
    }

    function getCheminImage()
    {
        return null;
    }
}

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
    $f->addEMail('fiche/adelec', 14);
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

  function renderSuccession($g, $f)
  {
    extract($this->data_annee);
    $p = $f->getParent();
    $p->removeChild($f);

    $l = $p->addList()->addFlags('vignettes');
    $s = $l->addItem()->addFlags('individu')->addSection('predecesseur');
    $s->addChild($this->view->vignetteIndividu($predecesseur));
    $s->addChild($this->view->cvScout($cv_predecesseur));

    $l->addItem(' et ')->addFlags('liaison');
    $s = $l->addItem()->addFlags('individu')->addSection('successeur');
    if (!$individu)
        $individu = new IndividuTemporaire($model->data->get());
    $s->addChild($this->view->vignetteIndividu($individu));
    if ($cv)
        $s->addChild($this->view->cvScout($cv));

    $p->addChild($f);
    $f->addParagraph($predecesseur." est déjà ".$app_predecesseur.". ".
		     "Succéder ".$individu." à ".$predecesseur->getFullname()." ?")
      ->addFlags('info');

    $c = $f->addForm_Compound();
    $c->addCheck('succession/succeder')->useLabel(true);
    $c->addDate('succession/date', '%e-%m-%Y');
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
