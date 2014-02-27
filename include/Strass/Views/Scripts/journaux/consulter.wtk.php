<?php

$this->document->addStyleComponents('signature', 'article');
$s = $this->document;
$ss = $s->addSection('boulet');
$ss->addFlags('article', 'boulet');
$ss->addText($this->article->boulet);

$ss = $s->addSection('article');
$ss->addFlags('article');
$t = $ss->addText($this->article->article);
$t->getTextWiki();

$p = $s->addParagraph($this->signature($this->article));
$p->addFlags('signature');
