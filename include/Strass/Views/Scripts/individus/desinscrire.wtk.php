<?php

$s = $this->document->addSection('inscription');
$s->addChild($this->vignetteIndividu($this->individu));
$s->addChild($this->cvScout(array($this->app)));
$f = $this->document->addForm($this->model);
$f->addCheck('confirmer');
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('continuer'));
