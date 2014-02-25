<?php

$f = $this->document->addForm($this->model);
$f->addEntry('nom');
$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
