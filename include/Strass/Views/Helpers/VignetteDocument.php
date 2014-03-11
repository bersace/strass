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
    if ($url = $document->getCheminVignette())
      $image = new Wtk_Image($document->getCheminVignette(),
			     $document->titre, $document->titre);
    else {
      $image = new Wtk_Paragraph("Pas d'aperçu");
      $image->addFlags('image', 'empty');
    }
    $item = new Wtk_Link($document->getUri(), $label, $image);

    $item->addFlags('vignette', 'document', $document->suffixe)
      ->addParagraph($label)->addFlags('label');

    return $item;
  }
}
