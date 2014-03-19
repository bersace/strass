<?php

if ($this->fermees->count()) {
  $l = $this->document->addList();
  $l->addFlags('vignettes', 'unites');
  foreach ($this->fermees as $unite) {
    $annee = $unite->getDerniereAnnee();
    $item = $l->addItem($this->vignetteUnite($unite, $annee, null, array('action' => 'index')));
    $item->addFlags('vignette');
  }
}
else {
  $this->document->addParagraph()->addFlags('empty')
    ->addInline("Pas d'unités fermées !");
}
