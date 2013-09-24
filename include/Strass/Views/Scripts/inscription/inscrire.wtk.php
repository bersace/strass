<?php

$s = $this->content->addSection('inscrire', new Wtk_Container(new Wtk_Inline("Inscrire dans "),
							      $this->lienUnite($this->unite)));
$f = $s->addChild(new Wtk_Form($this->model));

try {
	$g = $f->addChild(new Wtk_Form_Fieldset('Informations personnelles'));

	$g->addEntry('prenom');
	$g->addEntry('nom');
	if ($this->model->getInstance('sexe')->count()>1) {
		$g->addSelect('sexe', false, true); // selection simple, compacte
	}
	else {
		$g->addHidden('sexe');
	}
	$g->addDate('naissance', '%e/%m/%Y');
	try {
		$g->addEntry('totem');
	}catch(Exception $e){}

	$g = $f->addChild(new Wtk_Form_Fieldset('Contacts'));
	$g->addEntry('fixe', 14);
	$g->addEntry('portable', 14);
	$g->addEntry('adelec', 32);
} catch(Exception $e) {
	$f->removeChild($g);
}

$g = $f->addChild(new Wtk_Form_Fieldset('Inscription'));
$g->addSelect('appartenance/role', true);
$g->addDate('appartenance/debut', '%e/%m/%Y');
$g->addCheck('appartenance/accompli');
$g->addDate('appartenance/fin', '%e/%m/%Y');

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('inscrire')));
