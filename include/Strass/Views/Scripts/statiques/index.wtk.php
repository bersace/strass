<?php
$s = $this->document->addSection('page');
if ($this->wiki) {
  $s->addText($this->wiki);
}
else {
  $s->addParagraph("Pas de contenu")->addFlags('empty');
}

