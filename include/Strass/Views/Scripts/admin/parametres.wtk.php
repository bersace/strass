<?php

$f = $this->document->addForm($this->model);
$g = $f->addForm_Fieldset('metas');
$g->addEntry('title', 38);
$g->addEntry('short_title', 8);
$g->addEntry('subject', 38);
$g->addEntry('author', 24);
$g->addEntry('creation', 4);

$g = $f->addForm_Fieldset('system');
$g->addEntry('id', 8);
if (count($this->model->getInstance('system/style')) > 1)
  $g->addSelect('style');
else
  $g->addHidden('style');

$g->addEntry('admin', 24);
$g->addCheck('mail/enable');
$g->addEntry('mail/smtp', 24);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
