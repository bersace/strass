<?php

$s = $this->document->addSection('editer', 'Éditer '.$this->statique->getId());

$f = $s->addForm($this->model);
$f->addEntry('wiki', 64, 12)->useLabel(false);
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('enregistrer'));

