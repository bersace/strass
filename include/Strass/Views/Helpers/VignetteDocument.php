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
    $label = $document->titre;
    $wrapper = new Wtk_Section;
    $wrapper->addFlags('wrapper');
    if (in_array($document->suffixe, array('ogg', 'mp3', 'm4a'))) {
      $url =$document->getFichier();
      $wrapper->addAudio(array('url' => $url,
			       /* c'est sale */
			       'type' =>'audio/'.$document->suffixe));
    }
    else {
      if ($url = $document->getURLVignette())
	$wrapper->addImage($document->getURLVignette(),
			   $document->titre, $document->titre);
      else
	$wrapper->addParagraph("Pas d'aperçu")->addFlags('image', 'empty');
    }

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
