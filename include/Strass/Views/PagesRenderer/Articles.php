<?php

class Strass_Views_PagesRenderer_Articles extends Wtk_Pages_Renderer
{
    protected $view;

    function __construct($view)
    {
        $href = $view->url(array('page' => '%i'));
        parent::__construct(urldecode($href), true, array(
            'previous' => "Précédents",
            'next' => "Suivants"));
        $this->view = $view;
    }

    function render($id, $article, $root)
    {
        try {
            $d = $article->findDocument();
        }
        catch (Strass_Db_Table_NotFound $e) {
            $d = null;
        }

        $s = $root->addSection($article->slug, $this->view->lienArticle($article));
        $s->addFlags('article');
        $s->addChild($this->view->vignetteIndividu($article->findAuteur())->addFlags('mini'));

        if ($d) {
            $s->addFlags('carte', 'document', $d->suffixe);
            $s->addChild($this->view->vignetteDocument($d)->addFlags('nolabel'));
            $l = $s->addList()->addFlags('infos');
            $l->addItem()->addFlags('telechargement')
              ->addLink($d->getUri(), "Télécharger");
            $l->addItem("Format ".strtoupper($d->suffixe))
              ->addFlags('format');
            $l->addItem(wtk_format_size($d->getTaille()))
              ->addFlags('taille');
        }
        else {
            $contenu = $s->addSection()->addFlags('contenu');
            $t = $contenu->addText(wtk_first_lines($article->getBoulet() . "\n\n". $article->article));
            $tw = $t->getTextWiki();
            $tw->setRenderConf('Xhtml', 'image', 'base', $article->getDossier());
            $contenu->addParagraph($this->view->signature($article), ".")->addFlags('signature');
            $lien = $this->view->lienArticle($article, 'Lire la suite…');
            $contenu->addParagraph($lien)->addFlags('suite');
        }
    }
}
