<?php

$f = $this->document->addForm($this->model);

$i = $this->model->getInstance('parente');
if (count($i) > 1) {
  $f->addSelect('parente', true);
}
else {
  $f->addHidden('parente');
}

$f->addEntry('nom', 24);
$i = $this->model->getInstance('extra');
if ($i->label) {
	$f->addEntry('extra', 32);
 }
$f->addFile('image');
$f->addEntry('presentation', 38, 8);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
