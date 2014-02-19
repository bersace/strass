<?php

$i = $this->inscription;
$f = $this->document->addForm($this->model);

$enum = array('accepter' => 'acceptée',
	      'refuser' => 'refusée');

$f->addParagraph()->addFlags('warn')
->addInline("Êtes-vous **sûr** de l'authenticité de cette inscription ?");

if ($this->individu)
  $f->addParagraph($this->lienIndividu($this->individu),
		   " a sa fiche dans la base.")->addFlags('info');

$f->addEntry('prenom', 24)->setReadonly($this->individu);
$f->addEntry('nom', 24)->setReadonly($this->individu);

$s = $f->addSection('presentation');
$s->addParagraph($i->adelec)->addFlags('auteur');
$s->addText($i->presentation);

$f->addEntry('message', 48, 4);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('accepter'));
$b->addForm_Submit($this->model->getSubmission('refuser'))->addFlags('critical');
