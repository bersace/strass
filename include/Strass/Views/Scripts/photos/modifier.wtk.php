<?php

$s = $this->document->addSection('modifier', "Modifier « ".$this->photo->titre." »");

$v = $s->addSection('vignette');
$v->addChild(new Wtk_Paragraph($this->vignettePhoto($this->photo)));
$v->addFlags('vignette');

$f = $s->addChild(new Wtk_Form($this->model));
$i = $this->model->getInstance('activite');
if ($i->count() > 1) {
	$f->addParagraph()
		->addFlags('info')
		->addInline("Sélectionnez l'activité durant laquelle cette photo a été prise.");
	$f->addSelect('activite', true);
 }
$f->addChild('Entry', 'titre', 48);

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('enregistrer')));

