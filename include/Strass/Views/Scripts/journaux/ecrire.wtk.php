<?php

$s = $this->document->addSection('ecrire', new Wtk_Container($this->lienJournal($this->journal),
							    ($this->rubrique ? new Wtk_Inline(" : ") : null),
							    $this->lienRubrique($this->rubrique),
							    new Wtk_Inline(" : Écrire un article")));
$f = $s->addChild(new Wtk_Form($this->model));

// Sélecteur de rubrique
$i = $this->model->getInstance('rubrique');
if (count($i) > 1 ) {
	$f->addSelect('rubrique');
 }
 else {
	 $items = $i->getItems();
	 $rub = $items[$i->get()];
	 $f->addHidden('rubrique');
	 $f->addParagraph()->addFlags('info')
		 ->addInline("Votre article sera publié dans la rubrique ".$rub.".");
 }

// titre
$f->addEntry('titre', 48);

// publication
try {
	$f->addSelect('public');
}
catch(Exception $e) {
	$f->addParagraph()->addFlags('info')
		->addInline("Votre article sera en attente de modération.");
}

// article
$f->addEntry('boulet', 64, 4);
$f->addEntry('article', 64, 16);

// images
$g = $f->addChild(new Wtk_Form_Fieldset("Images", $this->model));
$g->addTable('images', array('image'	=> array('File'),
			     'nom'	=> array('Entry', 16)));

// boutons
$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('poster')));
