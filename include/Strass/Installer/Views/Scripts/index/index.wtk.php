<?php

class Strass_Pages_Renderer_Install extends Wtk_Pages_Renderer_Form
{
  protected $view;

  function __construct($view)
  {
    parent::__construct();
    $this->view = $view;
  }

  function renderSite($g, $f)
  {
    $this->view->document->addFlags('site');
       $f->addSelect('site/mouvement', false);
  }

  function renderAdmin($g, $f)
  {
    $d = $this->view->document->addDialog("Initialisation")
      ->setId('wait');

    $d->addImage('/data/install/loading.gif', 'loading', 'loading');
    $d->addParagraph("Veuillez patienter…");


    $this->view->document->addFlags('admin');
    $f->addEntry('admin/prenom', 24);
    $f->addEntry('admin/nom', 24);
    $f->addSelect('admin/sexe', false);
    $f->addEntry('admin/adelec', 32);
    $f->addPassword('admin/motdepasse', 12);
    $f->addPassword('admin/confirmation', 12);
    $f->dojoType = 'strass.install.Wizard';
  }
}

$this->document->addPages(null, $this->model,
			  new Strass_Pages_Renderer_Install($this));
