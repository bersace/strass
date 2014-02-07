<?php

class Strass_Page_RendererArticle extends Wtk_Pages_Renderer
{
  protected $view;
  protected $root;

  function __construct($view, $root)
  {
    $href = $view->url(array('journal' => $view->journal->slug,
			     'page' => '%i'));
    parent::__construct(urldecode($href),
			true,
			array('previous' => "Précédents",
			      'next' => "Suivants"));
    $this->view = $view;
    $this->root = $root;
  }

  function renderContainer()
  {
    return new Wtk_Container();
  }

  function render($id, $article, $root)
  {
    $s = $root->addSection($article->id, $this->view->lienArticle($article));
    $s->level = $this->root->level+1; // chaîner avec la section parente.

    // n'affiche le boulet ou à défaut le début de l'article.
    $boulet = $article->boulet ? $article->boulet : wtk_first_words($article->article);
    $t = $s->addText($boulet);
    $tw = $t->getTextWiki();
    $tw->setRenderConf('Xhtml', 'image', 'base', $article->getDossier());

    $s->addParagraph($this->view->signature($article), ".")->addFlags('signature');
    $lien = $this->view->lienArticle($article, 'Lire la suite…');
    $s->addParagraph($lien)->addFlags('suite');
  }
}

$this->document->addStyleComponents('signature');
$s = $this->document->addSection('journal');
if ($this->current < 2 && $this->editorial) {
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

if (count($this->articles)) {
  $s->addPages(null,
	       new Wtk_Pages_Model_Iterator($this->articles, 5, $this->current),
	       new Strass_Page_RendererArticle($this, $s));
}