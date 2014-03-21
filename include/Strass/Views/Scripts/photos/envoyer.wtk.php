<?php

$f = $this->document->addForm($this->model);
$i = $this->model->getInstance('activite');
if ($i->count() > 1) {
  $f->addParagraph()
    ->addFlags('info')
    ->addInline("Sélectionnez l'activité durant laquelle la photo à été prise.");
  $f->addSelect('activite', true);
 }
else {
  $f->addHidden('activite');
  $f->addParagraph()
    ->addFlags('info')
    ->addInline("Vous envoyez une photo pour **".$this->activite."**.");
 }
$f->addFile('photo');
$f->addEntry('titre', 32);
$f->addEntry('commentaire', 38, 8);
$f->addCheck('envoyer');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('envoyer'));
