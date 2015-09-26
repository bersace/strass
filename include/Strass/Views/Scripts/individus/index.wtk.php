<?php

class Strass_Pages_Renderer_Individus extends Wtk_Pages_Renderer
{
    private $view;

    function __construct($view)
    {
        $this->view = $view;
        parent::__construct($view->url(array('page' => '%i')), true,
        array('previous' => "Précédents",
        'next'	 => "Suivants"));
    }

    function renderContainer()
    {
        $l = new Wtk_List;
        $l->addFlags('individus', 'annuaire');
        return $l;
    }

    function render($id, $i, $parent)
    {
        $s = $parent->addItem()->addSection(
            null, $this->view->lienIndividu($i, $i->getFullName(false, false)));
        $s->addFlags('cartevisite');
        $s->addChild($this->view->vignetteIndividu($i)->addFlags('nolabel', 'mini'));

        $l = $s->addList()->addFlags('infos');
        if ($e = $i->findParentEtapes())
            $l->addItem()->addFlags('etape', $e->slug)
              ->addInline("**".$e->titre."**");
        if ($adelec = $i->adelec)
            $l->addItem()->addFlags('adelec')->addLink("mailto:".$adelec, $adelec);
        if ($telephone = $i->getTelephone())
            $l->addItem()->addFlags('telephone')->addLink("tel:".$telephone, $telephone);
    }
}

$f = $this->document->addForm($this->recherche)->addFlags('recherche');
$f->addEntry('recherche', 24)->useLabel(false);
$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->recherche->getSubmission('chercher'));

$this->document->addPages(null, $this->individus, new Strass_Pages_Renderer_Individus($this));
