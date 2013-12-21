<?php

$s = $this->document->addSection(null, "Ajouter des liens");
$f = $s->addChild(new Wtk_Form($this->model));
$f->addChild('Table', 'liens', array('url'		=> array('Entry', 24),
				     'nom'		=> array('Entry', 24),
				     'description'	=> array('Entry', 32, 3)));

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('ajouter')));
