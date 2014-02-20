<?php

$f = $this->document->addForm($this->model);
$i = $this->model->getInstance('unites');
if (count($i) > 1) {
  $f->addSelect('unites', true);
}
else {
   $f->addHidden('unites');
}
$f->addEntry('lieu', 32);
$c = $f->addDate('debut', 'le %d-%m-%Y à %H heures %M');
$c = $f->addDate('fin', 'le %d-%m-%Y à %H heures %M');
$f->addEntry('intitule',32);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
