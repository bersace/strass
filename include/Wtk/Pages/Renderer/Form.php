<?php

class Wtk_Pages_Renderer_Form extends Wtk_Pages_Renderer
{
  protected	$model;
  protected	$form;

  function __construct($model)
  {
    parent::__construct(null);
    $this->model = $model;
    $this->model->addNewSubmission('continuer', 'Continuer');
    $this->model->addNewSubmission('terminer', 'Terminer');
  }

  function renderContainer($root = null)
  {
    $this->form = $f = new Wtk_Form($this->model);
    // render all other fields in Hidden control up to current page.
    $current = $this->model->get('$$current$$');
    $root = $this->model->getInstance();
    foreach($root as $group) {
      if ($group->id != $current) {
	$this->renderInstance($group, $f);
      }
    }

    return $f;
  }

  function renderInstance($instance, $f)
  {
    if ($instance instanceof Wtk_Form_Model_Instance_Group) {
      foreach($instance as $child) {
	$this->renderInstance($child, $f);
      }
    }
    else if ($instance->id != '$$validated$$') {
      $f->addHidden($instance);
    }
  }

  function render($id, $data, $container)
  {
    $method = 'render'.ucfirst($id);
    call_user_func(array($this, $method), $data, $container);
  }

  function renderLinks($pages, $model)
  {
    $pids = $model->getPagesIds();
    $cid =$model->getCurrentPageId();
    $submit = $cid == end($pids) ? 'terminer' : 'continuer';
    $b = $this->form->addForm_ButtonBox();
    $b->addForm_Submit($this->model->getSubmission($submit));
  }
}