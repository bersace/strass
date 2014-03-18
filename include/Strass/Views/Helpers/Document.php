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
    $l->addItem()->addFlags('telechargement')
      ->addLink($document->getUri(), "Télécharger");
    if ($document->auteur)
      $l->addItem("Par ".$document->auteur)->addFlags('auteur');
    $l->addItem("Publié le ".strftime("%x", strtotime($document->date)))
      ->addFlags('date');
    $l->addItem("Format ".strtoupper($document->suffixe))
      ->addFlags('format');
    $l->addItem(wtk_format_size($document->getTaille()))
      ->addFlags('taille');

    if ($document->description)
      $s->addSection('description')
	->addText($document->description);

    return $s;
  }
}
