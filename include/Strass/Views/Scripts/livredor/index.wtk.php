<?php

class Strass_Pages_Renderer_Livredor extends Strass_Pages_Renderer
{
  function renderContainer()
  {
    $c = parent::renderContainer();

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
    $f->addEntry('contenu', 48, 6);
    $f->addForm_ButtonBox()->addForm_Submit($this->view->form_model->getSubmission('poster'));

    return $c;
  }

  function renderEmpty($container)
  {
    return $container->addParagraph("Pas de messages")->addFlags('empty');
  }

  function render($id, $message, $cont)
  {
    $s = $cont->addChild($this->view->Livredor($message));

    $resource = $message->getTable();
    if (Zend_Registry::get('acl')->isAllowed(Zend_Registry::get('user'), $resource, 'admin')) {
      $l = $s->addList()->addFlags('adminlinks');
      $l->addItem()->addChild($this->view->lien(array('controller' => 'livredor',
						      'action' => 'editer',
						      'message' => $message->id),
						"Éditer", true));
      $l->addItem()->addChild($this->view->lien(array('controller' => 'livredor',
						      'action' => 'supprimer',
						      'message' => $message->id),
						"Supprimer", true))
	->addFlags('warn');
    }
  }
}

$this->document->addStyleComponents('signature');
$renderer = new Strass_Pages_Renderer_Livredor($this, $this->url(array('page' => '%i')));
$p = $this->document->addPages(null, $this->page_model, $renderer);
