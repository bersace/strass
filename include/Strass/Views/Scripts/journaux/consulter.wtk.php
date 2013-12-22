<?php

$this->document->addStyleComponents('signature', 'article');
$s = $this->document;
$s->addParagraph(new Wtk_Inline("//in// "),
		 $this->lienJournal($this->journal),
		 ", ",
		 $this->lienRubrique($this->rubrique),
		 ".");

$ss = $s->addSection('boulet');
$ss->addFlags('article', 'boulet', $this->article->rubrique);
$t = $ss->addText($this->article->boulet);
$tw = $t->getTextWiki();
$tw->setRenderConf('Xhtml', 'image', 'base', $this->article->getDossier());

$ss = $s->addSection('article');
$ss->addFlags('article', $this->article->rubrique);
$t = $ss->addText($this->article->article);
$tw = $t->getTextWiki();
$tw->setRenderConf('Xhtml', 'image', 'base', $this->article->getDossier());

$p = $s->addChild(new Wtk_Paragraph($this->signature($this->article)));
$p->addFlags('signature');
