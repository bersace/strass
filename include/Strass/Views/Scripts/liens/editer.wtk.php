<?php

$f = $this->document->addForm($this->model);
$f->addTable('liens', array(
    'url' => array('Url', 24),
    'nom' => array('Entry', 24),
    'description' => array('Entry', 32, 2)));

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
