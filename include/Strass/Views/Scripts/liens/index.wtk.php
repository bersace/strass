<?php
$l = $this->document->addList();
foreach($this->liens as $lien) {
  $i = $l->addItem();
  $i->addParagraph()->addLink($lien->url, $lien->nom);
  $i->addParagraph()->addFlags('description')->addInline($lien->description);
}