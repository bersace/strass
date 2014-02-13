<?php

class Strass_Pages_RendererInstall extends Wtk_Pages_Renderer_Form
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
    $this->view->document->addFlags('admin');
    $f->addEntry('admin/prenom', 24);
    $f->addEntry('admin/nom', 24);
    $f->addEntry('admin/adelec', 32);
    $f->addPassword('admin/motdepasse', 12);
    $f->addPassword('admin/confirmation', 12);
  }
}

$this->document->addPages(null, $this->model,
			  new Strass_Pages_RendererInstall($this));
