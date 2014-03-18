<?php

if ($this->docs->count()) {
  $l = $this->document->addList()->addFlags('vignettes', 'documents');
  foreach($this->docs as $doc)
    $l->addItem($this->vignetteDocument($doc, array('controller' => 'documents',
						    'action' => 'details',
						    'document' => $doc->slug)));
}
else {
  $this->document->addParagraph('Aucun document')->addFlags('empty');
}
