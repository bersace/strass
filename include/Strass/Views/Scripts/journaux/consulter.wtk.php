<?php

$this->document->addStyleComponents('signature', 'article');
$s = $this->document;
$s->addParagraph(new Wtk_Inline("//in// "),
		 $this->lienJournal($this->article->findParentJournaux()),
		 ".");

$ss = $s->addSection('boulet');
$ss->addFlags('article', 'boulet');
$t = $ss->addText($this->article->boulet);
$tw = $t->getTextWiki();
$tw->setRenderConf('Xhtml', 'image', 'base', $this->article->getDossier());

$ss = $s->addSection('article');
$ss->addFlags('article');
$t = $ss->addText($this->article->article);
$tw = $t->getTextWiki();
$tw->setRenderConf('Xhtml', 'image', 'base', $this->article->getDossier());

$p = $s->addChild(new Wtk_Paragraph($this->signature($this->article)));
$p->addFlags('signature');
