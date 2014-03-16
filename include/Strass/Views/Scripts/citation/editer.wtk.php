<?php

$f = $this->document->addForm($this->model);
$f->addEntry('auteur', 24, 2);
$f->addEntry('texte', 38, 4)->useLabel(false);
$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
