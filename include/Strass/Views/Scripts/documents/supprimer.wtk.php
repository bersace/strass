<?php

$v = $this->document->addSection('vignette');
$v->addChild($this->vignetteDocument($this->doc));
$v->addFlags('vignette');

$f = $this->document->addForm($this->model);
$f->addCheck('confirmer');

$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('supprimer'));
