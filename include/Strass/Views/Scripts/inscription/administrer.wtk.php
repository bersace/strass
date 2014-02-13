<?php

$s = $this->document->setTitle(new Wtk_Container(new Wtk_RawText("Administrer "),
						 $this->lienIndividu($this->individu)));

$s = $this->document->addSection(null, "Ses unités");
if ($this->apps) {
  $f = $s->addForm($this->apps);
  $f->addTable('appartenances',
	       array('unite'	=> array('Select', true),
		     'role'	=> array('Select', true),
		     'debut'	=> array('Date', '%e-%m-%Y'),
		     'clore'	=> array('Check'),
		     'fin'	=> array('Date', '%e-%m-%Y')));

  $b = $f->addForm_ButtonBox();
  $b->addForm_Submit($this->apps->getSubmission('enregistrer'));
}
else {
  $s->addParagraph('Aucune inscription')->addFlags('empty');
}
