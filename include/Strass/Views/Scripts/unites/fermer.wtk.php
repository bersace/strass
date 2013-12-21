<?php

$s = $this->document->addSection(null,
				new Wtk_Container(new Wtk_Inline("Fermer l'unité "),
						  $this->lienUnite($this->unite)));
$f = $s->addChild(new Wtk_Form($this->model));
$f->addDate('fin', '%e/%m/%Y');

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('continuer')));
