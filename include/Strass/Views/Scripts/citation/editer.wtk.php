<?php

$f = $this->document->addForm($this->model);
$f->addEntry('auteur', 24);
$f->addEntry('texte', 64, 2);
$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
