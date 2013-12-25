<?php

class Strass_Pages_RendererCitation extends Strass_Pages_Renderer
{
  function render($listid, $citation, $cont)
  {
    $s = $cont->addSection()->addFlags('citation');
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
$renderer = new Strass_Pages_RendererCitation($this, $this->url(array('page' => '%i')),
					      true, Strass_Pages_Renderer::$feminin);
$this->document->addPages(null, $this->model, $renderer);
