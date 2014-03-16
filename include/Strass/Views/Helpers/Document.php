<?php

class Strass_View_Helper_Document
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function document($document)
  {
    $s = new Wtk_Section(null, $document->titre);
    $s->addFlags('document', $document->suffixe);
    $s->addChild($this->view->vignetteDocument($document)->addFlags('nolabel'));
    $l = $s->addList()->addFlags('infos');
    if ($document->auteur)
      $l->addItem("Par ".$document->auteur)->addFlags('auteur');
    $l->addItem("Publié le ".strftime("%x", strtotime($document->date)))
      ->addFlags('date');
    $l->addItem("Format ".strtoupper($document->suffixe))
      ->addFlags('format');


    if (!$this->view->assert(null, $document, 'editer'))
      return $s;

    $al = $s->addList()->addFlags('adminlinks');
    $al->addItem()->addChild($this->view->lien(array('controller' => 'documents',
						     'action' => 'envoyer',
						     'document' => $document->slug),
					       "Éditer", true))
      ->addFlags('adminlink', 'editer');
    $al->addItem()->addChild($this->view->lien(array('controller' => 'documents',
						     'action' => 'supprimer',
						     'document' => $document->slug),
					       "Supprimer", true))
      ->addFlags('adminlink', 'supprimer', 'critical');

    return $s;
  }
}
