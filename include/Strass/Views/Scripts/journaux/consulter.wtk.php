<?php

$this->document->addStyleComponents('signature', 'article');

$s = $this->document->addSection('boulet');
$s->addFlags('article', 'boulet');
$t = $s->addText($this->article->getBoulet());
$tw = $t->getTextWiki();
$tw->setRenderConf('Xhtml', 'image', 'base', $this->article->getDossier());

$s = $this->document->addSection('article');
$s->addFlags('article');
$t = $s->addText($this->article->article);
$tw = $t->getTextWiki();
$tw->setRenderConf('Xhtml', 'image', 'base', $this->article->getDossier());

$p = $this->document->addParagraph($this->signature($this->article));
$p->addFlags('signature');
