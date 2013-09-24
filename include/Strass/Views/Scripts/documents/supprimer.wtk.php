<?php

$s = $this->content->addSection('supprimer', "Supprimer un document");

$f = $s->addForm($this->model);
$f->addSelect('documents', true);  // compact


$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('supprimer'));
