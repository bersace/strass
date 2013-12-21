<?php

$s = $this->document->addSection('commenter', array("Commenter « ".$this->photo->titre." »"));

$v = $s->addSection('vignette');
$v->addChild(new Wtk_Paragraph($this->vignettePhoto($this->photo)));
$v->addFlags('vignette');


$f = $s->addForm($this->model);
$f->addEntry('commentaire', 64, 8);
try {
	$f->addCheck('supprimer');
}
catch (Exception $e) {}

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('commenter'));
