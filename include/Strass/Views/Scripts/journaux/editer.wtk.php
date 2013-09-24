<?php

$s = $this->content->addSection('editer', new Wtk_Container($this->lienJournal($this->journal),
							    new Wtk_Inline(" : Éditer "),
							    $this->lienArticle($this->article)));
$f = $s->addChild(new Wtk_Form($this->model));
$g = $f->addChild(new Wtk_Form_Fieldset('Métadonnées'));
$g->addEntry('titre', 48);
// Sélecteur de rubrique
$i = $this->model->getInstance('rubrique');
if (count($i) > 1 ) {
	$f->addSelect('rubrique');
 }
 else {
	 $items = $i->getItems();
	 $rub = $items[$i->get()];
	 $g->addHidden('rubrique');
	 $g->addParagraph()->addFlags('info')
		 ->addInline("Votre article est publié dans la rubrique ".$rub.".");
 }

try {
	$g->addSelect('auteur');
} catch(Exception $e) {}
$g->addSelect('public');

$g = $f->addChild(new Wtk_Form_Fieldset('Boulet //(facultatif)//'));
$g->addEntry('boulet', 64, 4)->useLabel(false);

$g = $f->addChild(new Wtk_Form_Fieldset('Article'));
$g->addEntry('article', 64, 16)->useLabel(false);

$i = $this->model->getInstance('images');
if ($i->count()) {
  $g = $f->addChild(new Wtk_Form_Fieldset("Images"));
  $g->addTable('images', array('id'	=> array('Hidden'),
			       'nom'	=> array('Entry', 16)));
 }

$g = $f->addChild(new Wtk_Form_Fieldset("Nouvelles images"));
$g->addTable('nvimgs', array('image'	=> array('File'),
			     'nom'	=> array('Entry', 16)));

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('enregistrer')));
