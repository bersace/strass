<?php

$this->document->setTitle(new Wtk_Container("Détruire ", $this->lienUnite($this->unite)));

$f = $this->document->addForm($this->model);
$f->addCheck('confirmer');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('continuer'));
