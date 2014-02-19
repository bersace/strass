<?php

class Strass_Pages_Renderer_Inscrire extends Wtk_Pages_Renderer_Form
{
  protected $view;

  function __construct($view, $model)
  {
    parent::__construct($model);
    $this->view = $view;
  }

  function renderActuel($group, $f)
  {
    $f->addDate('actuel/date', '%e-%m-%Y');
    $g = $f->getModel()->getInstance('actuel/apps');
    foreach ($g as $i) {
      $f->addCheck($i);
    }

    try {
      $f->addCheck('actuel/inscrire');
      $f->addSelect('actuel/unite', true);
    }
    catch(Exception $e) {}
  }

  function renderRole($group, $f)
  {
    $f->addSelect('role/role', true);
    $c = $f->addForm_Compound();
    $c->addCheck('role/clore')->useLabel(true);
    $c->addDate('role/fin');
  }
}

$renderer = new Strass_Pages_Renderer_Inscrire($this, $this->model->getFormModel());
$this->document->addPages(null, $this->model, $renderer, false);
