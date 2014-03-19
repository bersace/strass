<?php

if ($this->photos->count()) {
  $l = $this->document->addList();
  $l->addFlags('vignettes', 'photos');
  foreach($this->photos as $photo) {
    $i = $l->addItem($this->vignettePhoto($photo));
    $i->addFlags('vignette');
  }
}
else {
  $this->document->addParagraph("Pas de photos")->addFlags('empty');
}
