<?php

class Strass_Views_PagesRenderer_Articles extends Wtk_Pages_Renderer
{
  protected $view;

  function __construct($view)
  {
    $href = $view->url(array('page' => '%i'));
    parent::__construct(urldecode($href),
			true,
			array('previous' => "Précédents",
			      'next' => "Suivants"));
    $this->view = $view;
  }

  function render($id, $article, $root)
  {
    $s = $root->addSection($article->slug, $this->view->lienArticle($article));
    $s->addFlags('article');
    $s->addChild($this->view->vignetteIndividu($article->findAuteur()));

    $t = $s->addText(wtk_first_lines($article->getBoulet() . "\n\n". $article->article));
    $tw = $t->getTextWiki();
    $tw->setRenderConf('Xhtml', 'image', 'base', $article->getDossier());
    $s->addParagraph($this->view->signature($article), ".")->addFlags('signature');
    $lien = $this->view->lienArticle($article, 'Lire la suite…');
    $s->addParagraph($lien)->addFlags('suite');
  }
}
