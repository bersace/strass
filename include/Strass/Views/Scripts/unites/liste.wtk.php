<?php

if ($this->model) {
	$f = $this->document->addForm($this->model);
	$g = $f->addForm_Fieldset('Colonnes');
	$g->addSelect('existantes');
	$g = $f->addForm_Fieldset('Colonnes supplémentaires');
	$g->addParagraph("Lister des noms de colonnes qui seront ajoutées, vous permettant de compléter manuellement la liste imprimée.")->addFlags('info');
	$g->addTable('supplementaires',
			  array('nom' => array('Entry')));
	$f->addSelect('format');
	$b = $f->addChild(new Wtk_Form_ButtonBox());
	$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('lister')));
	return;
 }

$s = $this->document->addFlags($this->unite->type);

if ($this->apps->count() || count($this->sousunites)) {
	$ss = $s->addSection("effectifs", "Effectifs ".$this->annee." - ".($this->annee+1))->addFlags('effectifs');
	if (!$this->unite->isTerminale()) {
		$sss = $ss->addSection('maitrise', "Maîtrise");
	}
	else {
		$sss = $ss;
	}

	if ($this->apps->count()) {
		$t = $sss->addChild($this->tableEffectifs($this->appsTableModel($this->apps),
							  $this->fiches, 'liste', $this->existantes, $this->supplementaires));
		$t->addFlags($this->unite->type);
	}

	foreach($this->sousunites as $unite) {
		$apps = $this->sousapps[$unite->id];
		if ($apps instanceof Iterator) {
			$sss = $ss->addSection($unite->id,
					       $this->lienUnite($unite, null, null, false));
			$t = $sss->addChild($this->tableEffectifs($this->appsTableModel($apps),
								  $this->fiches, 'liste', $this->existantes, $this->supplementaires));
			$t->addFlags($unite->type);
			$t->show_header = false;
		}
	}
 }
