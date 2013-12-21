<?php

$s = $this->document->addSection(null, 'Enregistrer une citation');
$f = $s->addForm($this->model);
$f->addEntry('auteur', 24);
$f->addEntry('citation', 64, 2);
$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
