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
    $item = new Wtk_Link($document->getUri(), $label,
			 new Wtk_Image($document->getCheminVignette(),
				       $document->titre, $document->titre));
    $item->addFlags('vignette', 'document')
      ->addParagraph($label);

    return $item;
  }
}
