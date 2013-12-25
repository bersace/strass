<?php

$this->document->setTitle(new Wtk_Container(new Wtk_Inline("Désinscrire "),
					    $this->lienIndividu($this->individu)));
$f = $this->document->addForm($this->model);
$f->addCheck('confirmer');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('continuer'));
