<?php

$i = $this->model->getInstance('unites');
$pour = (count($i) == 1 ? " pour ".current(current($i)) :"");
$this->document->setTitle("Prévoir une nouvelle activité".$pour);

$s = $this->document;
$f = $s->addChild(new Wtk_Form($this->model));
$g = $f->addForm_Fieldset('Informations générales');
$i = $this->model->getInstance('unites');
if (count($i) > 1) {
	$g->addSelect('unites');
 }
 else {
	 $g->addHidden('unites');
 }
$g->addEntry('lieu', 32);

$c = $g->addForm_Compound('Aller');
$c->addDate('debut', 'le %d-%m-%Y à %H heures %M');
$c->addEntry('depart', 32);

$c = $g->addForm_Compound('Retour');
$c->addDate('fin', 'le %d-%m-%Y à %H heures %M');
$c->addEntry('retour', 32);

$g->addParagraph()->addFlags('info')
->addChild("Laisser ce champ vide et l'intitulé sera généré, sinon le remplir sans date.");
$g->addEntry('intitule', 48);

$g = $f->addForm_Fieldset('Chaîne');
$g->addSpin('cotisation', ' €');
$g->addTable('apporter', array('item' => array('Entry', 32)));
$g->addEntry('message', 64, 9)->useLabel(false);


$g = $f->addForm_Fieldset('Attacher un document');
$g->addTable('attacher',
	     array('document'	=> array('Select', true)));

$g = $f->addForm_Fieldset('Pièces-Jointes');
$g->addTable('documents',
	     array('fichier' 	=> array('File'),
		   'titre'	=> array('Entry', 32)));

$f->addCheck('prevoir');

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('ajouter')));
