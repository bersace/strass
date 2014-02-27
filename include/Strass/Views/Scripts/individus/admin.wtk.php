<?php

if ($this->apps) {
  $f = $this->document->addForm($this->apps);
  $f->addTable('appartenances',
	       array('unite'	=> array('Select', true),
		     'role'	=> array('Select', true),
		     'titre'	=> array('Entry', 8),
		     'debut'	=> array('Date', '%e-%m-%Y'),
		     'clore'	=> array('Check'),
		     'fin'	=> array('Date', '%e-%m-%Y')));

  $b = $f->addForm_ButtonBox();
  $b->addForm_Submit($this->apps->getSubmission('enregistrer'));
}
else {
  $s->addParagraph('Aucune inscription')->addFlags('empty');
}
