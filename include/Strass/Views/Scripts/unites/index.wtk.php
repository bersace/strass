<?php

class Strass_Views_Unite_Index_BlocGenerator
{
    function __construct($view)
    {
        $this->view = $view;
    }

    function render()
    {
        foreach ($this->view->blocs as $bloc) {
            $method = 'bloc'.$bloc;
            if (!method_exists($this, $method))
                throw new Exception("Impossible de générer le bloc ".$bloc);
            call_user_func(array($this, $method));
        }
    }

    function blocUnites()
    {
        $s = $this->view->document;
        $unite = $this->view->unite;
        $unites = $this->view->unites;

        $ss = $s->addSection('unites', 'Les '.$unite->getSousTypeName(true));
        $ss->addFlags('bloc');
        if ($unites->count()) {
            $l = $ss->addList();
            $l->addFlags('vignettes', 'unites');
            foreach ($unites as $unite)
                $l->addItem($this->view->vignetteUnite($unite));
        }
        else {
            $ss->addParagraph()->addFlags('empty')
               ->addInline("Pas d'unités actives !");
        }
    }

    function blocPhotos()
    {
        $s = $this->view->document;
        $unite = $this->view->unite;
        $photos = $this->view->photos;

        $ss = $s->addSection(
            'photos',
            $this->view->lien(
                array(
                    'controller' => 'photos',
                    'action' => null,
                    'unite' => $unite->slug),
                'Les photos des activités', true));
        $ss->addFlags('bloc');
        if ($photos->count()) {
            $l = $ss->addList();
            $l->addFlags('vignettes', 'photos');
            foreach($photos as $photo) {
                $i = $l->addItem($this->view->vignettePhoto($photo));
                $i->addFlags('vignette');
            }
        }
        else {
            $ss->addParagraph()->addFlags('empty')
               ->addInline("Pas de photos d'activités !");
        }
    }

    function blocActivites()
    {
        $s = $this->view->document;
        $unite = $this->view->unite;
        $activites = $this->view->activites;

        $ss = $s->addSection('activites',
        $this->view->lien(
            array('controller' => 'activites',
            'action' => 'calendrier',
            'unite' => $unite->slug),
            'Activités marquantes', true));
        $ss->addFlags('bloc');
        if ($activites->count()) {
            $l = $ss->addList();
            $l->addFlags('vignettes', 'activites');
            foreach($activites as $activite) {
                $l->addItem(
                    $this->view->vignettePhoto($activite->getPhotoAleatoire(),
                    $activite->getIntituleCourt(),
                    array(
                        'controller'    => 'activites',
                        'action'        => 'consulter',
                        'activite'  => $activite->slug),
                    true));
            }
        }
        else {
            $ss->addParagraph()->addFlags('empty')
               ->addInline("Pas d'activités marquantes !");
        }
    }

    function blocDocuments()
    {
        $s = $this->view->document;
        $unite = $this->view->unite;
        $documents = $this->view->documents;

        $ss = $s->addSection('documents',
        $this->view->lien(
            array('controller' => 'documents',
            'action' => 'index',
            'unite' => $unite->slug),
            'Documents', true));
        $ss->addFlags('bloc');
        if ($documents->count()) {
            $l = $ss->addList();
            $l->addFlags('vignettes', 'documents');
            foreach($documents as $document) {
                $l->addItem(
                    $this->view->vignetteDocument($document,
                    array(
                        'controller' => 'documents',
                        'action' => 'details',
                        'document' => $document->slug)));
            }
        }
        else {
            $ss->addParagraph()->addFlags('empty')
               ->addInline("Pas de documents !");
        }
    }
}

$src = $this->unite->getCheminImage();
if ($this->presentation || $src) {
    $s = $this->document->addSection('presentation');

    if ($src)
        $s->addImage($src, "Photos d'unité", $this->unite->getFullname());

    $s->addText($this->presentation);
}

$generator = new Strass_Views_Unite_Index_BlocGenerator($this);
$generator->render();
