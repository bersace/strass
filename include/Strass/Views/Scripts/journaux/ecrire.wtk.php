<?php

$f = $this->document->addForm($this->model);

$f->addEntry('titre', 46);
$f->addEntry('boulet', 64, 4);
$f->addEntry('article', 64, 16);

$f->addTable('images', array('image'  => array('File'),
			     'nom'    => array('Entry', 16),
			     'origin' => array('Hidden')));

try {
  $f->addSelect('public');
}
catch(Exception $e) {
  $f->addParagraph()->addFlags('info')
    ->addInline("Votre article sera en attente de modération.");
}

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('poster'));
