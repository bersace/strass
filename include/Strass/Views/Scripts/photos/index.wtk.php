<?php

class Strass_Pages_Renderer_Albums extends Strass_Views_PagesRenderer_Historique
{
  function render($annee = NULL, $data, $parent)
  {
    extract($data);
    $l = $parent->addList()->addFlags('albums', 'vignettes');
    if ($albums->count()) {
      foreach ($albums as $album) {
	$l->addItem($this->view->vignetteAlbum($album));
      }
    }
    else {
      $parent->addParagraph("Pas de photos de l'année ".$annee." !")->addFlags('empty');
    }
  }
}

$this->document->addPages('albums', $this->model, new Strass_Pages_Renderer_Albums($this));
