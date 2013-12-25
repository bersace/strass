<?php

$s = $this->document;
$s->addStyleComponents('signature');
$s->setTitle("Supprimer le message de ".$this->message->auteur);

$ss = $s->addSection()->addFlags('message');
$ss->addText($this->message->message);
$auteur = $this->message->adelec ?
  "[mailto:".$this->message->adelec." ".$this->message->auteur."]" :
  $this->message->auteur;
$ss->addParagraph(new Wtk_Inline('posté par **'.$auteur.'** '.
				'le '.strftime('%d-%m-%Y', strtotime($this->message->date)).'.'))
->addFlags('signature');

$f = $s->addForm($this->model);
$f->addCheck('confirmer');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('continuer'));
