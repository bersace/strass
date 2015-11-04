<?php

$this->document->setTitle($this->lienArticle($this->article));

if ($this->doc) {
    $s = $this->document->addChild($this->document($this->doc));
}
else {
    $s = $this->document->addSection('article', $this->article->titre)
                        ->addFlags('article');

    $s->addChild($this->vignetteIndividu($this->article->findAuteur())
                      ->addFlags('mini'));

    if ($boulet = $this->article->getBoulet()) {
        $ss = $s->addSection('boulet');
        $t = $ss->addText($this->article->getBoulet());
        $tw = $t->getTextWiki();
        $tw->setRenderConf('Xhtml', 'image', 'base', $this->article->getDossier());
    }

    $ss = $s->addSection('contenu');
    $t = $ss->addText($this->article->article);
    $tw = $t->getTextWiki();
    $tw->setRenderConf('Xhtml', 'image', 'base', $this->article->getDossier());

    $p = $s->addParagraph($this->signature($this->article))
           ->addFlags('signature');
}