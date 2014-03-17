<?php

$f = $this->document->addForm($this->model);
$g = $f->addForm_Fieldset("Informations générales");
$i = $this->model->getInstance('unites');
if (count($i) > 1) {
  $g->addSelect('unites', true);
}
else {
  $g->addHidden('unites');
}
$g->addEntry('lieu', 32);
$g->addDate('debut', 'le %d-%m-%Y à %H heures %M');
$g->addDate('fin', 'le %d-%m-%Y à %H heures %M');
$g->addEntry('intitule',32);
$g->addEntry('description', 32, 8)->useLabel(false);

$g = $f->addForm_Fieldset("Pièces-jointes");
$g->addTable('documents', array('document'  => array('Select', true),
				'fichier'  => array('File'),
				'titre'    => array('Entry', 16),
				'origin'   => array('Hidden')));

$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('enregistrer'));
