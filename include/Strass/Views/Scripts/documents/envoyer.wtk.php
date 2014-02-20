<?php

$i = $this->model->getInstance('unite');
$f = $this->document->addForm($this->model);
if (count($i) > 1) {
  $f->addSelect('unite');
 }
 else {
   $f->addHidden('unite');
 }
$f->addEntry('titre', 36);
$f->addFile('document');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('envoyer'));
