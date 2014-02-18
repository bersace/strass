<?php

if ($this->docs->count()) {
  $l = $this->document->addList()
    ->addFlags('vignettes', 'documents');

  foreach($this->docs as $doc) {
    $l->addItem($this->vignetteDocument($doc))
      ->addFlags('vignette', 'document');
  }
}
else {
  $this->document->addParagraph('Aucun document')->addFlags('empty');
}
