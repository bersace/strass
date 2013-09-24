<?php

$i = $this->model->getInstance('unite');
$s = $this->content->addSection('documents', "Envoyer un document".(count($i) == 1 ? " à l'unité ".current(current($i)) : ""));
$f = $s->addChild(new Wtk_Form($this->model));
if (count($i) > 1) {
  $f->addChild('Select', 'unite');
 }
 else {
   $f->addChild('Hidden', 'unite');
 }
$f->addChild('Entry', 'titre', 36);
$f->addChild('File', 'document');

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('envoyer')));
