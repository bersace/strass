<?php

class Strass_Pages_RendererCitation extends Strass_Pages_Renderer
{
  function render($listid, $citation, $cont)
  {
    $s = $cont->addChild($this->view->citation($citation));

    $resource = $citation->getTable();
    if ($this->view->assert(null, $resource, 'admin')) {
      $l = $s->addList()->addFlags('adminlinks');
      $l->addItem()->addChild($this->view->lien(array('controller' => 'citation',
						      'action' => 'editer',
						      'citation' => $citation->id),
						"Éditer", true))
	->addFlags('adminlink', 'editer');
      $l->addItem()->addChild($this->view->lien(array('controller' => 'citation',
						      'action' => 'supprimer',
						      'citation' => $citation->id),
						"Supprimer", true))
	->addFlags('adminlink', 'supprimer', 'critical');
    }
  }
}

$renderer = new Strass_Pages_RendererCitation($this, $this->url(array('page' => '%i')),
					      true, Strass_Pages_Renderer::$feminin);
$this->document->addPages(null, $this->model, $renderer);
