<?php
$s = $this->content->addSection('historique',
				new Wtk_Container(new Wtk_RawText("Compléter l'effectif de "),
						  $this->lienUnite($this->unite)));

$f = $s->addChild(new Wtk_Form($this->model));
$f->addSelect('individu', true);
$f->addSelect('role', true);
$f->addDate('debut', '%e/%m/%Y');
$f->addCheck('clore');
$f->addDate('fin', '%e/%m/%Y');

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('valider')));
