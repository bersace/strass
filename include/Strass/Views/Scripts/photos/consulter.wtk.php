<?php
$this->document->addStyleComponents('vignette');
$s = $this->document->addSection('photos');
$l = $s->addChild(new Wtk_List());
$l->addFlags('vignettes');
foreach($this->photos as $photo) {
  $i = $l->addItem($this->vignettePhoto($photo));
  $i->addFlags('vignette');
}
