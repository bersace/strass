<?php
$this->document->setTitle(new Wtk_Container(new Wtk_RawText("Éditer "),
					    $this->lienUnite($this->unite)));

$f = $this->document->addForm($this->model);

$f->addEntry('nom', 24);
$i = $this->model->getInstance('extra');
if ($i->label) {
	$f->addEntry('extra', 32);
 }
$f->addFile('image');
$f->addEntry('presentation', 38, 8);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
