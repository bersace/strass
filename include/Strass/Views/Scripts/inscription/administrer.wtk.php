<?php

$s = $this->content->addSection(null, new Wtk_Container(new Wtk_RawText("Gérer les appartenances de "),
							$this->lienIndividu($this->individu)));
$f = $s->addChild(new Wtk_Form($this->apps));
$f->addTable('appartenances',
	     array('unite'	=> array('Select', true),
		   'role'	=> array('Select', true),
		   'debut'	=> array('Date', '%e-%m-%Y'),
		   'clore'	=> array('Check'),
		   'fin'	=> array('Date', '%e-%m-%Y')));


$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->apps->getSubmission('enregistrer')));



// PROGRESSION
if ($this->progression->getInstance('progression')->count()) {
	$s = $this->content->addSection(null, new Wtk_Container(new Wtk_RawText("Gérer la progression de "),
								$this->lienIndividu($this->individu)));
	$f = $s->addChild(new Wtk_Form($this->progression));
	$f->addTable('progression',
		     array('etape'	=> array('Select', true),
			   'lieu'	=> array('Entry'),
			   'date'	=> array('Date', '%e-%m-%Y'),
			   'details'	=> array('Entry', 16, 3)));

	$b = $f->addChild(new Wtk_Form_ButtonBox());
	$b->addChild(new Wtk_Form_Submit($this->progression->getSubmission('enregistrer')));
 }


// FORMATION

if ($this->formation->getInstance('formation')->count()) {
	$s = $this->content->addSection(null, new Wtk_Container(new Wtk_RawText("Gérer la formation de "),
								$this->lienIndividu($this->individu)));
	$f = $s->addChild(new Wtk_Form($this->formation));
	$f->addTable('formation',
		     array('diplome'	=> array('Select', true),
			   'date'	=> array('Date', '%e-%m-%Y')));


	$b = $f->addChild(new Wtk_Form_ButtonBox());
	$b->addChild(new Wtk_Form_Submit($this->formation->getSubmission('enregistrer')));
 }