<?php

$s = $this->document->addSection('poster');
$f = $s->addForm($this->model);

$f->addEntry('auteur', 24);
$f->addEntry('contenu', 48, 6);
$f->addCheck('public');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
