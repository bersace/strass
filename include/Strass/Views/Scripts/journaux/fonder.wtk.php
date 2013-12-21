<?php

$s = $this->document->addSection("fonder-journal", "Fonder le journal pour ".$this->unite);
$f = $s->addChild(new Wtk_Form($this->model));
$f->addEntry('nom');
$g = $f->addChild(new Wtk_Form_Fieldset("Rubriques", $this->model));
$g->addTable('rubriques', array('nom' => array('Entry', 16)));

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('fonder')));
