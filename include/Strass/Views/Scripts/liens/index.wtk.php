<?php
if ($this->liens->count()) {
  $l = $this->document->addList();
  foreach($this->liens as $lien) {
    $i = $l->addItem();
    $i->addParagraph()->addFlags('lien')->addLink($lien->url, $lien->nom);
    $i->addParagraph()->addFlags('description')->addInline($lien->description);
  }
}
else {
  $this->document->addParagraph('Aucun lien')
    ->addFlags('empty');
}
