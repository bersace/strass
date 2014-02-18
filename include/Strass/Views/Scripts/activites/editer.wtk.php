<?php

$f = $this->document->addForm($this->model);
$g = $f->addForm_Fieldset('Informations générales');
$i = $this->model->getInstance('unites');
if (count($i) > 1) {
  $g->addSelect('unites', true);
}
else {
   $g->addHidden('unites');
}
$g->addEntry('lieu', 32);
$c = $g->addDate('debut', 'le %d-%m-%Y à %H heures %M');
$c = $g->addDate('fin', 'le %d-%m-%Y à %H heures %M');
$g->addEntry('intitule',32);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
