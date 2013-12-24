<?php

class Scout_Pages_RendererCitation extends Wtk_Pages_Renderer
{
  function __construct($view, $href, $intermediate, $model) {
    parent::__construct($href, $intermediate, $model);
    $this->view = $view;
  }

  function renderContainer($root = null)
  {
    return new Wtk_List;
  }

  function render($listid, $citation, $liste)
  {
    $s = $liste->addItem()->addSection()->addFlags('citation');
    $s->addParagraph("« ".$citation->texte." »")->addFlags('citation');
    $s->addParagraph($citation->auteur)->addFlags('signature');

    $resource = $citation->getTable();
    if (Zend_Registry::get('acl')->isAllowed(Zend_Registry::get('individu'), $resource, 'admin')) {
      $l = $s->addList()->addFlags('adminlinks');
      $l->addItem()->addChild($this->view->lien(array('controller' => 'citation',
						      'action' => 'editer',
						      'citation' => $citation->id),
						"Éditer", true));
      $l->addItem()->addChild($this->view->lien(array('controller' => 'citation',
						      'action' => 'supprimer',
						      'citation' => $citation->id),
						"Supprimer", true));
    }
  }
}

$this->document->addStyleComponents('signature');
$s = $this->document;
$s->addPages(null,
	     $this->model,
	     new Scout_Pages_RendererCitation($this,
					      $this->url(array('page' => '%i')),
					      true,
					      array('previous' => 'Précédentes',
						    'next' => 'Suivantes')));
