<?php

// ANTI ABSENTS
if ($this->activite->isFuture())
  $this->document->addDialog()
    ->addFlags('warn')
    ->addInline("**La présence de chacun est primordiale** ".
		"pour le bon déroulement de cette activité.");

$s = $this->document->addSection('evenement', $this->activite->getIntituleComplet());
$s->addChild($this->vignetteAlbum($this->activite)->addFlags('nolabel'));
$l = $s->addList()->addFlags('infos');
$l->addItem()->addFlags('debut')
->addInline("**Début :** ".strftime('le %A %e %B à %R', strtotime($this->activite->debut)));
$l->addItem()->addFlags('fin')
->addInline("**Fin :** ".strftime('le %A %e %B à %R', strtotime($this->activite->fin)));
if ($lieu = $this->activite->lieu)
  $l->addItem()->addFlags('lieu')->addInline("**Lieu :** ".$lieu);

$l->addItem()->addFlags('description')->addText($this->activite->description);

// PIÈCES JOINTES
if ($this->documents->count()) {
  $ss = $this->document->addSection('piecesjointes', "Les documents");
  $l = $ss->addList();
  $l->addFlags('vignettes', 'document');
  foreach($this->documents as $docact)
    $l->addItem($this->vignetteDocument($docact->findParentDocuments()))
    ->addFlags('vignette', 'document');
}

if (!$this->activite->isFuture()) {
  // PHOTOS
  $s = $this->document->addSection('photos', $this->lien(array('controller' => 'photos',
							       'action' => 'consulter',
							       'album' => $this->activite->slug),
							 "Les photos"), true);
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