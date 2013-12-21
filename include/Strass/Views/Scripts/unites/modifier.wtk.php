<?php
$section = $this->document->addSection(null,
				      new Wtk_Container(new Wtk_RawText("Modifier "),
							$this->lienUnite($this->unite)));

$f = $section->addChild(new Wtk_Form($this->model));

$f->addEntry('nom', 24);
$i = $this->model->getInstance('extra');
if ($i->label) {
	$f->addEntry('extra', 32);
 }
$f->addFile('image');
$f->addEntry('presentation', 64, 8);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('enregistrer'));
