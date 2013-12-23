<?php

$this->document->setTitle(new Wtk_Container(new Wtk_Inline("Modifier l'activité "),
						 $this->lienActivite($this->activite)));
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
$c = $g->addDate('debut', 'le %d-%m-%Y à %H heures %M');
$c = $g->addDate('fin', 'le %d-%m-%Y à %H heures %M');
$g->addEntry('intitule', 48);

try {
	$g = $f->addForm_Fieldset('Pièces-jointes');
	$g->addTable('documents/existants',
		     array('id'	=> array('Hidden'),
			   'titre'	=> array('Entry', 32)));
}
catch(Exception $e) {
	$f->removeChild($g);
}

$g = $f->addForm_Fieldset('Attacher un document');
$g->addTable('documents/attacher',
	     array('document'	=> array('Select', true)));

$g = $f->addForm_Fieldset('Envoyez de nouvelles pièces-jointes');
$g->addTable('documents/envois',
	     array('fichier'	=> array('File'),
		   'titre'	=> array('Entry', 32)));

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
