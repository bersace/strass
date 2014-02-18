<?php

$this->document->addStyleComponents('vignette');

$v = $this->document->addSection('vignette');
$v->addChild($this->vignettePhoto($this->photo));
$v->addFlags('vignette');

$f = $this->document->addForm($this->model);
$f->addCheck('confirmer');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('continuer'));
