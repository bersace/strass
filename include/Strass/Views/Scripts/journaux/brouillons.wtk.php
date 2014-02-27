<?php

class Strass_Pages_Renderer_Articles extends Wtk_Pages_Renderer
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
    $s = $root->addSection($article->id, $this->view->lienArticle($article, null, 'ecrire'));

    $t = $s->addText($article->getBoulet());
    $tw = $t->getTextWiki();
    $tw->setRenderConf('Xhtml', 'image', 'base', $article->getDossier());

    $s->addParagraph($this->view->signature($article), ".")->addFlags('signature');
    $lien = $this->view->lienArticle($article, 'Lire la suite…', 'ecrire');
    $s->addParagraph($lien)->addFlags('suite');
  }
}

$this->document->addPages(null, $this->model,  new Strass_Pages_Renderer_Articles($this));
