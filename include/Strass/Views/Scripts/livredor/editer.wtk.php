<?php

$f = $this->document->addForm($this->model);

$f->addEntry('auteur', 24);
$f->addEntry('adelec', 24);
$f->addEntry('message', 64, 2);
$f->addCheck('public');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
