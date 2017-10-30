<?php

$f = $this->document->addForm($this->model);
$g = $f->addForm_Fieldset('metas');
$g->addEntry('title', 38);
$g->addEntry('short_title', 8);
$g->addEntry('subject', 38);
$g->addEntry('author', 24);
$g->addEntry('creation', 4);

$g = $f->addForm_Fieldset('system');
if (count($this->model->getInstance('system/style')) > 1)
  $g->addSelect('style');
else
  $g->addHidden('style');

$g->addCheck('mail/enable');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
