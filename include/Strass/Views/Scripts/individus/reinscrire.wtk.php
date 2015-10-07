<?php

$l = $this->document->addList()->addFlags('vignettes');
$l->addItem($this->vignetteIndividu($this->individu));
$l->addItem(' et ')->addFlags('liaison');
$l->addItem($this->vignetteUnite($this->unite));

$f = $this->document->addForm($this->model);
$f->addSelect('role', true);
$f->addEntry('titre');
$f->addDate('debut', '%e/%m/%Y');
$c = $f->addForm_Compound();
$c->addCheck('clore')->useLabel(true);
$c->addDate('fin', '%e/%m/%Y');
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('enregistrer'));