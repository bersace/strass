<?php

$s = $this->document->addSection(null,
				new Wtk_Container("Enregistrer la progression de ",
						  $this->lienIndividu($this->individu)));

$f = $s->addForm($this->model);
$f->addSelect('etape', true);
$f->addDate('date', '%e-%m-%Y');
$f->addEntry('lieu');


$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
