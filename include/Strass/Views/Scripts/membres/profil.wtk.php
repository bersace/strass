<?php

$s = $this->content->addSection(null, 'Modifier votre profil');

$f = $s->addChild(new Wtk_Form($this->model));
$fs = $f->addChild(new Wtk_Form_Fieldset("Change le mot de passe"));
try {
  $fs->addPassword('mdp/ancien', 12);
}catch(Exception $e) {}
$fs->addPassword('mdp/nouveau', 12);
$fs->addPassword('mdp/confirmation', 12);
// $fs->addChild(new Wtk_Paragraph($this->lien(array('action' => 'mdperdu'),
// 					    "Mot de passe oublié")));

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('valider')));

try {
  $f = $s->addChild(new Wtk_Form($this->admin));
  $f->addCheck('admin');
  $b = $f->addChild(new Wtk_Form_ButtonBox());
  $b->addChild(new Wtk_Form_Submit($this->admin->getSubmission('valider')));
}catch(Exception $e) {}
