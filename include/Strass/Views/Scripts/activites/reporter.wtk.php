<?php

$s = $this->content->addSection('rapport',
				new Wtk_Container($this->lienActivite($this->activite),
						  new Wtk_Inline(" : "),
						  $this->lienUnite($this->unite),
						  new Wtk_Inline(" : Rapport")));
$f = $s->addChild(new Wtk_Form($this->model));

$g = $f->addChild(new Wtk_Form_Fieldset('Boulet'));
$g->addEntry('boulet', 72, 4)->useLabel(false);

$g = $f->addChild(new Wtk_Form_Fieldset('Rapport'));
$g->addEntry('rapport', 72, 18)->useLabel(false);

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('enregistrer')));
