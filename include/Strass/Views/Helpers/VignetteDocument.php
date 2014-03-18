<?php

class Strass_View_Helper_VignetteDocument
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function vignetteDocument($document, $urlOptions=null)
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
    if ($urlOptions)
      $url = $this->view->url($urlOptions, true, true);
    else
      $url = $document->getUri();
    $item = new Wtk_Link($url, $label, $wrapper);
    $item->addFlags('vignette', 'document', $document->suffixe)
      ->addParagraph($label)->addFlags('label');

    return $item;
  }
}
