<?php
$f = $this->document->addForm($this->model);

$f->addForm_Fieldset("Message d'accueil")
->addEntry('presentation', 38, 8)->useLabel(false);

$g = $this->model->getInstance('blocs');
$ff = $f->addForm_Fieldset("Blocs de la page d'accueil");
$t = $ff->addTable('blocs',
	      array('id' => array('Hidden'),
		    'enable' => array('Check')));
$t->table->addNewColumn('Bloc', new Wtk_Table_CellRenderer_Text('text', 'nom'));

$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('enregistrer'));
