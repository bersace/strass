<?php
// Laisser le contenu définir le titre du document
$this->document->setTitle(null);
if ($this->wiki) {
  $this->document->addText($this->wiki);
}
else {
  $this->document->addParagraph("Pas de contenu")->addFlags('empty');
}
