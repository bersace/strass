<?php

// ANTI ABSENTS
if ($this->activite->isFuture())
  $this->document->addDialog()
    ->addFlags('warn')
    ->addText("**La présence de chacun est primordiale** ".
	      "pour le bon déroulement de cette activité.");

$s = $this->document->addSection('evenement', $this->activite->getIntituleComplet());
$s->addChild($this->vignetteAlbum($this->activite)->addFlags('nolabel'));
$l = $s->addList()->addFlags('infos');
$datefmts = array('reunion' => 'le %A %e %B à %R',
		  'sortie' => 'le %A %e %B à %R',
		  'weekend' => 'le %A %e %B à %R',
		  'camp' => '%A %e %B',
		  );
$datefmt = $datefmts[$this->activite->getType()];
$l->addItem()->addFlags('debut')
->addInline("**Début :** ".strftime($datefmt, strtotime($this->activite->debut)));
$l->addItem()->addFlags('fin')
->addInline("**Fin :** ".strftime($datefmt, strtotime($this->activite->fin)));
if ($lieu = $this->activite->lieu)
  $l->addItem()->addFlags('lieu')->addInline("**Lieu :** ".$lieu);

$s->addSection('description')->addText($this->activite->description);

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