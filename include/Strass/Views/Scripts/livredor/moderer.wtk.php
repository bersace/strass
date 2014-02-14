<?php

class Strass_Pages_Renderer_Livredor extends Strass_Pages_Renderer
{
  function render($id, $message, $cont)
  {
    $s = $cont->addChild($this->view->Livredor($message));

    $l = $s->addList()->addFlags('adminlinks');
    $l->addItem()->addChild($this->view->lien(array('controller' => 'livredor',
						    'action' => 'accepter',
						    'message' => $message->id),
					      "Accepter", true));
    $l->addItem()->addChild($this->view->lien(array('controller' => 'livredor',
						    'action' => 'editer',
						    'message' => $message->id),
					      "Éditer", true));
    $l->addItem()->addChild($this->view->lien(array('controller' => 'livredor',
						    'action' => 'supprimer',
						    'message' => $message->id,
						    'redirect' => 'moderer'),
					      "Refuser", true))
      ->addFlags('warn');
  }
}

$this->document->addStyleComponents('signature');
$renderer = new Strass_Pages_Renderer_Livredor($this, $this->url(array('page' => '%i')));
$p = $this->document->addPages(null, $this->messages, $renderer);
