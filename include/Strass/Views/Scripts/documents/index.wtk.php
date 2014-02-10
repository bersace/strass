<?php

$l = $this->document->addList()
  ->addFlags('vignettes', 'documents');

foreach($this->docs as $doc) {
  $l->addItem($this->vignetteDocument($doc))
    ->addFlags('vignette', 'document');
}