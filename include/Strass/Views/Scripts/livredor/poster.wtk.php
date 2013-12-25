<?php

$s = $this->document;
$f = $s->addForm($this->model);
$f->addParagraph()->addFlags('info')
->addInline("Le livre d'or est **modéré**, ".
	    "ce **n'est pas un forum**. ".
	    "Veillez à vous exprimer en **bon français**.");
$f->addEntry('auteur');
$f->addEntry('adelec');
$f->addEntry('message', 64, 8);
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('poster'));
