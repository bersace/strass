<?php

class Strass_Pages_Renderer_Livredor extends Strass_Pages_Renderer
{
  function render($id, $message, $cont)
  {
    $s = $cont->addSection()->addFlags('message');
    $s->addText($message->message);
    $auteur = Zend_Registry::get('user') && $message->adelec ?
      "[mailto:".$message->adelec." ".$message->auteur."]" :
      $message->auteur;
    $s->addParagraph(new Wtk_Inline('posté par **'.$auteur.'** '.
				    'le '.strftime('%d-%m-%Y', strtotime($message->date)).'.'))
      ->addFlags('signature');

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
						"Supprimer", true));
    }
  }
}

$this->document->addStyleComponents('signature');
$renderer = new Strass_Pages_Renderer_Livredor($this, $this->url(array('page' => '%i')));
$p = $this->document->addPages(null, $this->model, $renderer);
