<?php

$f = $this->document->addForm($this->model);
$fs = $f->addForm_Fieldset("Changer le mot de passe");
$fs->addEntry('adelec', 24);
try {
  $fs->addPassword('mdp/ancien', 12);
}catch(Exception $e) {}
$fs->addPassword('mdp/nouveau', 12);
$fs->addPassword('mdp/confirmation', 12);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('valider'));

try {
  $f = $this->document->addForm($this->admin);
  $f->addCheck('admin');
  $b = $f->addForm_ButtonBox();
  $b->addForm_Submit($this->admin->getSubmission('valider'));
}catch(Exception $e) {}
