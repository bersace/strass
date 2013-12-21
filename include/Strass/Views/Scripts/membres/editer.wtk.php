<?php

$s = $this->document->addSection("Éditer la fiche d'inscription");

$f = $s->addForm($this->model);

$g = $f->addForm_Fieldset("Détails de la cotisation");
$g->addParagraph()->addFlags('info')
->addInline("Détailler ici le montant de la côtisation, l'ordre des chèques, etc.");
$g->addEntry('cotisation', 64, 8)->useLabel(false);

$g = $f->addForm_Fieldset("Informations demandées");
$g->addCheck('scoutisme');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('valider'));
