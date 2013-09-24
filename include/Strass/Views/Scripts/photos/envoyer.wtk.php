<?php

$s = $this->content->addSection('photos', "Envoyer une photo");
$f = $s->addChild(new Wtk_Form($this->model));
$i = $this->model->getInstance('activite');
if ($i->count() > 1) {
	$f->addParagraph()
		->addFlags('info')
		->addInline("Sélectionnez l'activité durant laquelle la photos que vous voulez envoyer à été prise.");
	$f->addSelect('activite', true);
 }
else {
	$f->addHidden('activite');
	$f->addParagraph()
		->addFlags('info')
		->addInline("Vous allez envoyer une photos pour l'activité ".$this->activite.".");
 }
$f->addFile('photo');
$f->addEntry('titre', 48);
$f->addEntry('commentaire', 64, 8);
$f->addCheck('envoyer');

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('envoyer')));
