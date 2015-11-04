<?php


$f = $this->document->addForm($this->model);
if ($this->model->getInstance('auteur') instanceof Wtk_Form_Model_Instance_Enum)
    $f->addSelect('auteur', true);
else
    $f->addHidden('auteur');
$f->addEntry('titre', 36);
try {
  $f->addSelect('public');
}
catch(Exception $e) {
  $f->addParagraph("Votre article sera en attente de modération.")->addFlags('info');
}
$f->addFile('fichier');
$f->addDate('date', '%e/%m/%Y');

$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('envoyer'));
