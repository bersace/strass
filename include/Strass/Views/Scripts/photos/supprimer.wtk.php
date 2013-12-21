<?php

$s = $this->document->addSection(null, "Supprimer la photo « ".$this->photo->titre." »");

$v = $s->addSection('vignette');
$v->addParagraph($this->vignettePhoto($this->photo));
$v->addFlags('vignette');


$f = $s->addForm($this->model);
$f->addCheck('confirmer');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('continuer'));
