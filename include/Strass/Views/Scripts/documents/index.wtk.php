<?php

if ($this->docs->count()) {
  $s = $this->document->addSection('documents')->addFlags('documents');
  foreach($this->docs as $doc)
    $s->addChild($this->document($doc));
}
else {
  $this->document->addParagraph('Aucun document')->addFlags('empty');
}
