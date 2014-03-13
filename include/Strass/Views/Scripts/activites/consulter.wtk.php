<?php

$this->document->addText($this->activite->description);

// PIÈCES JOINTES
if ($this->documents->count()) {
  $ss = $this->document->addSection('piecesjointes', "Pièces-jointes");
  $l = $ss->addList();
  $l->addFlags('vignettes', 'document');
  foreach($this->documents as $docact)
    $l->addItem($this->vignetteDocument($docact->findParentDocuments()))
    ->addFlags('vignette', 'document');
}

// ANTI ABSENTS
if ($this->activite->isFuture())
  $this->document->addParagraph()
    ->addFlags('remarque')
    ->addInline("**La présence de chacun est primordiale** ".
		"pour le bon déroulement de cette activité.");
else {
  // PHOTOS
  $s = $this->document->addSection('photos', $this->lien(array('controller' => 'photos',
							       'action' => 'consulter',
							       'album' => $this->activite->slug),
							 "Photos"), true);
  if ($this->photos->count()) {
    $l = $s->addList();
    $l->addFlags('vignettes', 'photos');
    foreach($this->photos as $photo) {
      $i = $l->addItem($this->vignettePhoto($photo));
      $i->addFlags('vignette');
    }
  }
  else {
    $s->addParagraph("Pas de photos")->addFlags('empty');
  }
}