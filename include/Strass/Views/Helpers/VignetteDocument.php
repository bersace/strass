<?php

class Strass_View_Helper_VignetteDocument
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function vignetteDocument($document)
  {
    $this->view->document->addStyleComponents('vignette');
    $label = $document->titre;
    $wrapper = new Wtk_Section;
    $wrapper->addFlags('wrapper');
    if ($url = $document->getCheminVignette())
      $wrapper->addImage($document->getCheminVignette(),
			 $document->titre, $document->titre);
    else
      $wrapper->addParagraph("Pas d'aperçu")->addFlags('image', 'empty');
    $item = new Wtk_Link($document->getUri(), $label, $wrapper);

    $item->addFlags('vignette', 'document', $document->suffixe)
      ->addParagraph($label)->addFlags('label');

    return $item;
  }
}
