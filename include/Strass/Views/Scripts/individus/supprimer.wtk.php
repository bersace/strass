<?php

$f = $this->document->addForm($this->model);
$f->addCheck('confirmer');

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('continuer'));
