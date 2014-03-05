<?php

$f = $this->document->addForm($this->model);
$f->addDate('fin', '%e/%m/%Y');
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('continuer'));
