<?php

$f = $this->document->addForm($this->model);

$g = $f->addForm_Fieldset("Informations");
$i = $this->model->getInstance('parente');
if (count($i) > 1)
  $g->addSelect('parente', true);
else
  $g->addHidden('parente');
$g->addEntry('nom', 24);
$i = $this->model->getInstance('extra');
if ($i->label)
  $g->addEntry('extra', 32);

$g = $f->addForm_Fieldset("Image d'unité");
$g->addSection('vignette')->addChild($this->vignetteUnite($this->unite));
if ($this->unite->getCheminImage())
  $g->addCheck('supprimer_image');
else
  $g->addParagraph("Pas d'image, repli sur une photo aléatoire.")->addFlags('info');
$g->addFile('image');

$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('enregistrer'));
