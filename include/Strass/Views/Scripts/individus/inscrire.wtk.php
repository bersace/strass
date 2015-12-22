<?php

class Strass_Pages_Renderer_Inscrire extends Wtk_Pages_Renderer_Form
{
    protected $view;

    function __construct($view, $model)
    {
        parent::__construct($model);
        $this->view = $view;
    }

    function renderActuel($g, $f)
    {
        $g = $g->getChild('apps');
        foreach ($g as $i) {
            $f->addCheck($i);
        }

        $f->addDate('actuel/date', '%e-%m-%Y');

        try {
            if ($g->count() > 0)
                $f->addCheck('actuel/inscrire');
            $f->addSelect('actuel/unite', true);
        }
        catch(Exception $e) {}
    }

    function renderRole($g, $f)
    {
        $f->addSelect('role/role', true);
        $c = $f->addForm_Compound();
        $c->addCheck('role/clore')->useLabel(true);
        $c->addDate('role/fin', '%e-%m-%Y');
    }

    function renderTitre($g, $f)
    {
        if ($g->getChild('predefini')->count() > 1)
            $f->addSelect('titre/predefini', true);
        else
            $f->addHidden('titre/predefini');
        $f->addEntry('titre/autre', 16);
    }
}


$this->document->addSection('vignette')->addChild($this->vignetteIndividu($this->individu));
$this->document->addChild($this->cvScout($this->apps, true)); /* Lien admin */
$renderer = new Strass_Pages_Renderer_Inscrire($this, $this->model->getFormModel());
$this->document->addPages(null, $this->model, $renderer, false);
