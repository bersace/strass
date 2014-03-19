<?php

$f = $this->document->addForm($this->model);

if ($this->model->getInstance('auteur') instanceof Wtk_Form_Model_Instance_Enum)
  $f->addSelect('auteur', true);
else
  $f->addHidden('auteur');

$f->addEntry('titre', 46);

try {
  $f->addSelect('public');
}
catch(Exception $e) {
  $f->addParagraph("Votre article sera en attente de modération.")->addFlags('info');
}

$f->addEntry('boulet', 64, 4);
$f->addEntry('article', 64, 16);

$f->addTable('images', array('image'  => array('File'),
			     'nom'    => array('Entry', 16),
			     'origin' => array('Hidden')));

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('poster'));
