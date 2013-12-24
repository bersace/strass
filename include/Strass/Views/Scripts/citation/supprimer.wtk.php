<?php

$s = $this->document;
$s->addStyleComponents('signature');
$s->setTitle("Supprimer une citation de ".$this->citation->auteur);

$v = $s->addSection('citation');
$s->addParagraph("« ".$this->citation->texte." »")->addFlags('citation');
$s->addParagraph($this->citation->auteur)->addFlags('signature');

$f = $s->addForm($this->model);
$f->addCheck('confirmer');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('continuer'));
