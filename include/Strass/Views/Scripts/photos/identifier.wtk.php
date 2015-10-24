<?php

$f = $this->document->addForm($this->model);
$v = $f->addSection('vignette')
       ->addChild($this->vignettePhoto($this->photo));
$f->addSelect('unites', false)->useLabel(false);
$f->addForm_ButtonBox()
  ->addForm_Submit($this->model->getSubmission('enregistrer'));
