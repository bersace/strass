<?php

class Strass_Pages_Renderer_Livredor extends Strass_Pages_Renderer
{
  function renderForm($c)
  {
    $current = $this->view->page_model->key();
    if ($current > 1)
      return $c;

    $s = $c->addSection('poster', "Poster un nouveau message");
    $s->level = 2;
    $f = $s->addForm($this->view->form_model);
    if ($f->model->get('auteur'))
      $f->addHidden('auteur');
    else
      $f->addEntry('auteur');
    $f->addEntry('contenu', 48, 6)->useLabel(false);
    $f->addForm_ButtonBox()->addForm_Submit($this->view->form_model->getSubmission('poster'));
  }

  function renderContainer()
  {
    $c = parent::renderContainer();
    $this->renderForm($c);
    return $c;
  }

  function renderEmpty($container)
  {
    $this->renderForm($container);
    $container->addParagraph("Pas de messages")->addFlags('empty');
    return $container;
  }

  function render($id, $message, $cont)
  {
    $s = $cont->addChild($this->view->Livredor($message));

    $resource = $message->getTable();
    if ($this->view->assert(null, $resource, 'admin')) {
      $l = $s->addList()->addFlags('adminlinks');
      $l->addItem()->addChild($this->view->lien(array('controller' => 'livredor',
						      'action' => 'editer',
						      'message' => $message->id),
						"Éditer", true))->addFlags('adminlink', 'editer');
      $l->addItem()->addChild($this->view->lien(array('controller' => 'livredor',
						      'action' => 'supprimer',
						      'message' => $message->id),
						"Supprimer", true))
	->addFlags('critical', 'adminlink', 'supprimer');
    }
  }
}

$renderer = new Strass_Pages_Renderer_Livredor($this, $this->url(array('page' => '%i')));
$p = $this->document->addPages(null, $this->page_model, $renderer);
