<?php

$i = $this->model->getInstance('unite');
$f = $this->document->addForm($this->model);
if (count($i) > 1)
  $f->addSelect('unite');
else
  $f->addHidden('unite');
$f->addFile('fichier');
$f->addEntry('titre', 36);
$f->addEntry('auteur', 36);
$f->addDate('date', '%e/%m/%Y');
$f->addEntry('description', 36, 4);

$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('envoyer'));
