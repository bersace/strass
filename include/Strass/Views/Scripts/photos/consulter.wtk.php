<?php
$this->document->addStyleComponents('vignette');
$s = $this->document;

if ($this->photos->count()) {
  $l = $s->addList();
  $l->addFlags('vignettes');
  foreach($this->photos as $photo) {
    $i = $l->addItem($this->vignettePhoto($photo));
    $i->addFlags('vignette');
  }
}
else {
  $s->addParagraph("Pas de photos")->addFlags('empty');
}
