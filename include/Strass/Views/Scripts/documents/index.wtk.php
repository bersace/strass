<?php

if ($this->docs->count()) {
  $l = $this->document->addList()
    ->addFlags('vignettes', 'documents');

  foreach($this->docs as $doc) {
    $i = $l->addItem($this->vignetteDocument($doc))
      ->addFlags('vignette', 'document');
    $al = $i->addList()->addFlags('adminlinks');
    $al->addItem()->addChild($this->lien(array('controller' => 'documents',
					       'action' => 'editer',
					       'document' => $doc->slug),
					 "Éditer", true));
    $al->addItem()->addChild($this->lien(array('controller' => 'documents',
					       'action' => 'supprimer',
					       'document' => $doc->slug),
					 "Supprimer", true))
      ->addFlags('critical');
  }
}
else {
  $this->document->addParagraph('Aucun document')->addFlags('empty');
}
