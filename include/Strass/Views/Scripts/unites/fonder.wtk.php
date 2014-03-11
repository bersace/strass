<?php

$f = $this->document->addForm($this->model);

$monotype = count($this->model->getInstance('type')) == 1;
$monoparente = count($this->model->getInstance('parente')) == 1;

if ($this->sousunite || $monoparente) {
  $f->addHidden('parente');
}
else {
  $f->addSelect('parente', true);
}

if ($monotype) {
  $f->addHidden('type');
}
else {
  $f->addSelect('type');
}

$i = $this->model->getInstance('nom');
if ($i instanceof Wtk_Form_Model_Instance_String) {
  $f->addEntry('nom', 24);
}
else {
  /* couleurs de sizaine, etc. */
  $f->addSelect('nom', true);
}

$i = $this->model->getInstance('extra');
/* Si le type est forcé et si l'extra a un nom (ex: cri de pat, etc.) */
if ($monotype && $i->label) {
  $f->addEntry('extra', 32);
}

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('fonder'));
