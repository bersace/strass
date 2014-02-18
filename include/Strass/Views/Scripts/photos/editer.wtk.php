<?php

$s = $this->document->addSection('photo');
$s->addParagraph()
->addFlags('photo')
->addImage($this->photo->getCheminImage(),
	   $this->photo->titre,
	   $this->photo->titre);

$f = $this->document->addForm($this->model);
$i = $this->model->getInstance('activite');
if ($i->count() > 1) {
  $f->addSelect('activite', true);
}
$f->addFile('photo');
$f->addEntry('titre', 32);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
