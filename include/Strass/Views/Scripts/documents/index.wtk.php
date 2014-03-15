<?php

if ($this->docs->count()) {
  $l = $this->document->addList()
    ->addFlags('vignettes', 'documents');

  foreach($this->docs as $doc) {
    $i = $l->addItem($this->vignetteDocument($doc))
      ->addFlags('vignette', 'document');
    $al = $i->addList()->addFlags('adminlinks');
    $al->addItem()->addChild($this->lien(array('controller' => 'documents',
					       'action' => 'envoyer',
					       'document' => $doc->slug),
					 "Éditer", true))
      ->addFlags('adminlink', 'editer');
    $al->addItem()->addChild($this->lien(array('controller' => 'documents',
					       'action' => 'supprimer',
					       'document' => $doc->slug),
					 "Supprimer", true))
      ->addFlags('adminlink', 'supprimer', 'critical');
  }
}
else {
  $this->document->addParagraph('Aucun document')->addFlags('empty');
}
