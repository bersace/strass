<?php

$f = $this->document->addForm($this->model);
$f->addEntry('wiki', 64, 24)->useLabel(false);
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('enregistrer'));
