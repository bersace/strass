<?php

$f = $this->document->addForm($this->model);
if ($this->photo)
  $f->addSection('vignette')->addChild($this->vignettePhoto($this->photo));

$f->addParagraph("Auteur : ", $this->lienIndividu($this->commentaire->findParentIndividus()));
$f->addEntry('message', 38, 8)->useLabel(false);
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('enregistrer'));
