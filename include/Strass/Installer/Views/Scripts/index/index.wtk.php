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
    $this->view->document->setTitle("Votre unité");
    $f->addSelect('site/mouvement', false);
  }

  function renderAdmin($g, $f)
  {
    $mouvement = $f->getModel()->get('site/mouvement');
    $this->view->document->setTitle("Votre compte");
    $this->view->document->addFlags($mouvement);
    $this->view->document->header->addFlags($mouvement);

    $d = $this->view->document->addDialog("Initialisation")
      ->setId('wait');

    $d->addImage('static/install/loading.gif', 'loading', 'loading');
    $d->addParagraph("Veuillez patienter…");


    $this->view->document->addFlags('admin');
    $f->addEntry('admin/prenom', 24);
    $f->addEntry('admin/nom', 24);
    $f->addSelect('admin/sexe', false);
    $f->addDate('admin/naissance', '%e-%m-%Y');
    $f->addEntry('admin/adelec', 24);
    $f->addPassword('admin/motdepasse', 12);
    $f->addPassword('admin/confirmation', 12);
    $f->dojoType = 'strass.install.Wizard';
  }
}

$this->document->addPages(null, $this->model, new Strass_Pages_Renderer_Install($this));
