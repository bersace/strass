<?php
$section = $this->document;

$f = $section->addChild(new Wtk_Form($this->model));

$g = $f->addForm_Fieldset("Détails de l'unité");
$tuc = count($this->model->getInstance('type'));
if ($tuc > 1) {
	$g->addSelect('type');
 }
 else {
	$g->addHidden('type');
 }
$i = $this->model->getInstance('nom');
if ($i instanceof Wtk_Form_Model_Instance_String) {
	$g->addEntry('nom', 24);
 }
 else {
	 $g->addSelect('nom', true);
 }

$i = $this->model->getInstance('extra');
if ($i->label && $tuc==1) {
	$g->addEntry('extra', 32);
 }
// $f->addChild('File', 'image');
// $f->addChild('ColorChooser', 'couleur_0');
// $f->addChild('ColorChooser', 'couleur_1');

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('fonder')));
