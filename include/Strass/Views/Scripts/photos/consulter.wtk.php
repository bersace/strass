<?php
$this->document->addStyleComponents('vignette');
$s = $this->content->addSection('photos');
$l = $s->addChild(new Wtk_List());
foreach($this->photos as $photo) {
  $i = $l->addItem($this->vignettePhoto($photo));
  $i->addFlags('vignette');
}
