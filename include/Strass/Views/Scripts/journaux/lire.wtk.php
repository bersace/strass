<?php
$this->document->addStyleComponents('signature');
$s = $this->document->addSection('journal');
if ($this->current < 2) {
	// ÉDITO
	if ($this->editorial) {
		$ss = $s->addSection('editorial', "Édito : ".$this->editorial->titre);
		$sss = $s->addSection();
		$sss->addFlags('article');
		// n'afficher que le résumé si disponible.
		if($this->editorial->boulet) {
			$t = $ss->addText($this->editorial->boulet);
		}
		// sinon, tout afficher.
		else {
			$t = $ss->addText($this->editorial->article);
		}

		$tw = $t->getTextWiki();
		$tw->setRenderConf('Xhtml', 'image', 'base', $this->editorial->getDossier());

		$ss->addParagraph($this->signature($this->editorial))->addFlags('signature');

		if ($this->editorial->boulet) {
			$ss->addParagraph($this->lienArticle($this->editorial, "Lire la suite …"))->addFlags('suite');
		}
	}

	$ss = $s->addSection('rubriques', "Rubriques");
	$l = $ss->addChild(new Wtk_List());
	foreach($this->rubriques as $rubrique) {
		$c = $this->rubsc[$rubrique->id];
		$l->addItem(new Wtk_Paragraph($this->lienRubrique($rubrique),
					      new Wtk_Inline(' ('.$c.')')))
			->addFlags($this->rubscl[$rubrique->id]);
	}
 }

if (count($this->articles)) {
	$ss = $this->current < 2 ? $s->addSection('articles', "Derniers articles") : $s;
	$ss->addChild(new Wtk_Pages(null,
				    new Wtk_Pages_Model_Iterator($this->articles, 5, $this->current),
				    new Strass_Page_RendererArticle($this, $ss)));
 }