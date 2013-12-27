<?php

if ($this->migrate) {
  $s = $this->document->addSection('migrate', "Utiliser ".$this->individu->adelec." pour s'identifier");
  $f = $s->addForm($this->migrate);
  $f->addSection()->addFlags('warn')
    ->addText("Pour plus de simplicité, utilisez votre adresse ".
	      "//".$this->individu->adelec."// plutôt que //".$this->user->username."// ".
	      "pour vous identifier. \n".
	      "**Voulez-vous migrer votre compte ?**");
  $f->addPassword('motdepasse', 12);
  $f->addForm_ButtonBox()->addForm_Submit($this->migrate->getSubmission('migrer'));
}

if ($this->adelec) {
  $s = $this->document->addSection('chpass', "Changer d'adresse électronique");
  $f = $s->addForm($this->adelec);
  $f->addEntry('adelec', 24);
  $f->addPassword('motdepasse', 12);
  $f->addForm_ButtonBox()->addForm_Submit($this->adelec->getSubmission('enregistrer'));
}

$s = $this->document->addSection('chpass', "Changer le mot de passe");
$f = $s->addForm($this->change);
try {
  $f->addPassword('mdp/ancien', 12);
} catch (Exception $e) {}
$f->addPassword('mdp/nouveau', 12);
$f->addPassword('mdp/confirmation', 12);
$f->addForm_ButtonBox()->addForm_Submit($this->change->getSubmission('valider'));

try {
  $s = $this->document->addSection('admin', "Établir les privilèges");
  $f = $s->addForm($this->admin);
  $f->addCheck('admin');
  $b = $f->addForm_ButtonBox();
  $b->addForm_Submit($this->admin->getSubmission('valider'));
} catch (Exception $e) {}
