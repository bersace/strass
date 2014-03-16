<?php
// Laisser le contenu définir le titre du document
$this->document->setTitle(null);
$s = $this->document->addSection('statique');
if ($this->wiki) {
  $s->addText($this->wiki);
}
else {
  $s->addParagraph("Pas de contenu")->addFlags('empty');
}
