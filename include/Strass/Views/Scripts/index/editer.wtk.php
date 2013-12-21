<?php

$s = $this->document->addSection(null, "Éditer le message de bienvenue");

$f = $s->addChild(new Wtk_Form($this->model));
$c = $f->addEntry('introduction', 64, 8);
$c->useLabel(false);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
