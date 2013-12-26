<?php

$s = $this->document->setTitle(new Wtk_Container(new Wtk_RawText("Administrer "),
						 $this->lienIndividu($this->individu)));

$s = $this->document->addSection(null, "Ses unités");
$f = $s->addForm($this->apps);
$f->addTable('appartenances',
	     array('unite'	=> array('Select', true),
		   'role'	=> array('Select', true),
		   'debut'	=> array('Date', '%e-%m-%Y'),
		   'clore'	=> array('Check'),
		   'fin'	=> array('Date', '%e-%m-%Y')));

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->apps->getSubmission('enregistrer'));

// PROGRESSION
if ($this->progression->getInstance('progression')->count()) {
  $s = $this->document->addSection(null, "Sa progression");
  $f = $s->addForm($this->progression);
  $f->addTable('progression',
	       array('etape'	=> array('Select', true),
		     'lieu'	=> array('Entry'),
		     'date'	=> array('Date', '%e-%m-%Y'),
		     'details'	=> array('Entry', 16, 3)));

  $b = $f->addForm_ButtonBox();
  $b->addForm_Submit($this->progression->getSubmission('enregistrer'));
 }

// FORMATION
if ($this->formation->getInstance('formation')->count()) {
  $s = $this->document->addSection(null, "Sa formation");
  $f = $s->addForm($this->formation);
  $f->addTable('formation',
	       array('diplome'	=> array('Select', true),
		     'date'	=> array('Date', '%e-%m-%Y')));


  $b = $f->addForm_ButtonBox();
  $b->addForm_Submit($this->formation->getSubmission('enregistrer'));
}