<?php

$i = $this->model->getInstance('unites');
$pour = (count($i) == 1 ? " pour ".current(current($i)) :"");
$this->document->setTitle("Prévoir une nouvelle activité".$pour);

$s = $this->document;
$f = $s->addForm($this->model);
$g = $f->addForm_Fieldset('Informations générales');
$i = $this->model->getInstance('unites');
if (count($i) > 1) {
  $g->addSelect('unites', true);
 }
 else {
   $g->addHidden('unites');
 }
$g->addEntry('lieu', 32);
$c = $g->addDate('debut', 'le %d-%m-%Y à %H heures %M');
$c = $g->addDate('fin', 'le %d-%m-%Y à %H heures %M');

$g->addParagraph()->addFlags('info')
->addChild("Laisser ce champ vide et l'intitulé sera généré, sinon le remplir sans date. ".
	   "(Ex: Rentrée, JN, RNR, Vezelay, etc.)");
$g->addEntry('intitule', 32);

$g = $f->addForm_Fieldset("Pièces-jointes");
$g->addTable('documents', array('fichier'  => array('File'),
				'titre'    => array('Entry', 16)));

$f->addCheck('prevoir');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('ajouter'));
