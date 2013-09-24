<?php

$s = $this->content->addSection('journal', "Modifier le journal « ".$this->journal->nom." »");
$f = $s->addChild(new Wtk_Form($this->model));
$f->addEntry('nom', 32);
$g = $f->addChild(new Wtk_Form_Fieldset("Rubriques"));
$g->addTable('rubriques', array('id'		=> array('Hidden'),
				'nom'		=> array('Entry', 16)));

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('enregistrer')));
