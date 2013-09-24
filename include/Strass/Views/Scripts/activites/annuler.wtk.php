<?php

$s = $this->content->addSection(null,
				new Wtk_Container(new Wtk_RawText("Annuler l'activité "),
						  $this->lienActivite($this->activite)));
$f = $s->addChild(new Wtk_Form($this->model));
$f->addCheck('confirmer');

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('continuer')));
