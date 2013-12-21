<?php

$s = $this->document->addSection('chaine', $this->activite->getIntitule());
$f = $s->addForm($this->model);
$f->addParagraph()->addFlags('info')->addInline("Voici le contenu du courriel, vous pouvez le complétez.");
$f->addEntry('intro', 64, 3)->useLabel(false);
$f->addText($this->intro);
$f->addSection(null, 'À apporter')->addChild($this->apporter);
$ss = $f->addSection(null, 'Détails');
$ss->addText($this->activite->message);
$f->addEntry('message', 64, 8)->useLabel(false);
$f->addText($this->warn);
$f->addEntry('signature', 64, 3)->useLabel(false);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('envoyer'));
