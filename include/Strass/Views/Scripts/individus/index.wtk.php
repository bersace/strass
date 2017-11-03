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
        $s->addFlags('carte', 'cartevisite');
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

    function renderEmpty($container)
    {
        return $container->addParagraph("Aucun résultat")->addFlags('empty');
    }
}

$this->document->addFlags('annuaire');

$f = $this->document->addForm($this->recherche)->addFlags('recherche');
$f->addSearch('recherche', 24)->useLabel(false)
  ->setPlaceHolder('prénom, nom, poste, patrouille, ...');
$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->recherche->getSubmission('chercher'));

$s = $this->document->addSection('filtres');
$l = $s->addList();

$filtres = array();
$filtres['tous'] = 'Tous';
$filtres['actifs'] = 'Actifs';
$filtres['anciens'] = 'Anciens';
if ($this->assert(null, null, 'totem') && Zend_Registry::get('individu')->totem)
    $filtres['sachem'] = 'Sachem';
$filtres['membres'] = 'Membres';
if ($this->assert(null, 'site', 'admin'))
    $filtres['admins'] = 'Administrateurs';

foreach($filtres as $filtre => $etiquette) {
    $i = $l->addItem();
    if ($this->filtre == $filtre)
        $i->addFlags('current');
    $i->addLink($this->url(array('filtre' => $filtre)), $etiquette);
}

$this->document->addPages(null, $this->individus, new Strass_Pages_Renderer_Individus($this));
