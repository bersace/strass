<?php

$s = $this->document;
$s->setTitle("Supprimer le message de ".$this->message->auteur);

$s->addChild($this->Livredor($this->message));

$f = $s->addForm($this->model);
$f->addCheck('confirmer');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('continuer'));
