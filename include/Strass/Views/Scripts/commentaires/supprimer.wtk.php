<?php

$this->document->addChild($this->commentaire($this->commentaire));

$f = $this->document->addForm($this->model);
$f->addCheck('confirmer');
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('continuer'));
