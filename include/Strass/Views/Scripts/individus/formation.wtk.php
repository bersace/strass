<?php

$this->document->setTitle(new Wtk_Container("Compléter la formation de ",
					    $this->lienIndividu($this->individu)));

$f = $this->document->addForm($this->model);
$f->addSelect('diplome', true);
$f->addDate('date', '%e-%m-%Y');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
