<?php

$s = $this->document->addSection('editer', new Wtk_Container(new Wtk_Inline("Modifier la fiche de "),
							    $this->lienIndividu($this->individu)));
$f = $s->addChild(new Wtk_Form($this->model));

$g = $f->addChild(new Wtk_Form_Fieldset('État civil'));
try {
	// ces champs ne sont pas forcément présent, soit parce que
	// seul l'admin peut le corriger, soit parce qu'il faut être
	// sachem pour y avoir accès.
	$g->addEntry('prenom', 24);
	$g->addEntry('nom', 24);
	$g->addDate('naissance', '%e/%m/%Y');
} catch(Exception $e){}

$g->addFile('image');
$g->addEntry('origine', 32);
$g->addEntry('situation', 32);

// SCOUTISME
$g = $f->addForm_Fieldset("Scoutisme");

try {
	$g->addEntry('totem', 24);
} catch(Exception $e){}

try {
	$g->addEntry('parrain', 24);
	$g->addEntry('perespi', 24);
	$g->addEntry('numero', 8);
} catch(Exception $e){}

// suppression si vide.
if (!$g->count())
	$f->removeChild($g);

// contacts
$g = $f->addChild(new Wtk_Form_Fieldset('Contacts'));
$g->addEntry('adresse', 48, 2);
$g->addEntry('fixe', 14);
$g->addEntry('portable', 14);
$g->addEntry('adelec', 24);
$g->addEntry('jabberid', 24);

$g = $f->addChild(new Wtk_Form_Fieldset('Notes'));
$g->addEntry('notes', 64, 8)->useLabel(false);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('valider'));
