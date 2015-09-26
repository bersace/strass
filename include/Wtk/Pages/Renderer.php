<?php

abstract class Wtk_Pages_Renderer
{
    protected   $labels = array(
        'previous'  => 'Previous',
        'next'      => 'Next');
    protected   $intermediate;
    protected   $href;
    protected   $model;
    public      $ellipsize;


    // Si $labels est null, les liens précédent/suivant ne seront
    // pas affichés.
    function __construct(
        $href, $intermediate = true, $labels = array(), $ellipsize=false)
    {
        if (is_null($labels) || count($labels))
            $this->labels = $labels;
        $this->href     = $href;
        $this->intermediate = $intermediate;
        $this->ellipsize = $ellipsize;
    }

    function renderContainer()
    {
        return new Wtk_Container();
    }

    abstract function render($id, $data, $container);


    function renderEmpty($container)
    {
        return $container->addParagraph("Pas de contenu")->addFlags('empty');
    }

    function renderLinks($pages, $model)
    {
        if ($model->pagesCount() == 1)
            return;

        $this->model = $model;

        $l = $pages->addChild(new Wtk_List());
        $l->addFlags('pages', 'links');

        if ($this->labels != null && $pid = $model->getPrevId()) {
            $i = $l->addItem($this->renderLink($pid, $this->labels['previous']));
            if ($i) {$i->addFlags('pages', 'previous'); }
        }

        if ($this->intermediate && $model->pagesCount()>1) {
            $ids = $model->getPagesIds();
            $current_k = array_search($model->getCurrentPageId(), $ids);
            $last_k = count($ids) - 1;
            $ellipsing = false;
            foreach($ids as $k => $id) {

                /* On éllipse si… */
                if (
                    /* c'est demandé */
                    $this->ellipsize
                    /* on a beaucoup de pages */
                    && $last_k > 16
                    /* la page n'est pas aux bordures (1 2 … 23 24) */
                    && 1 < $k && $k < ($last_k - 1)
                    && (
                        /* La page n'est pas seule à éluder entre le début et la
                         * courante (évite 1 2 … 4 5 6 … 23 24). */
                        (4 < $current_k&& $k < ($current_k - 1))
                        /* La page n'est pas seule à éluder entre la page
                         * courante et les dernières (évite 1 2 … 20 21 22 … 23
                         * 24). */
                        || ($current_k < ($last_k - 4) && ($current_k + 1) < $k))) {

                    if (!$ellipsing) {
                        $l->addItem('…')->addFlags('ellipsis');
                        $ellipsing = true;
                    }
                    continue;
                }
                else {
                    $ellipsing = false;
                }

                $i = $l->addItem($this->renderLink($id));
                if ($i) {
                    $i->addFlags('pages');
                    if ($id == $model->getCurrentPageId()) {
                        $i->addFlags('current');
                    }
                }
            }
        }

        if ($this->labels && $sid = $model->getNextId()) {
            $i = $l->addItem($this->renderLink($sid, $this->labels['next']));
            if ($i) {$i->addFlags('pages', 'next'); }
        }
    }

    protected function renderLink($id, $label = null)
    {
        if (!is_null($id)) {
            return new Wtk_Link(str_replace("%i", $id, $this->href),
            $label ? $label : $this->getLabel($id));
        }
        return NULL;
    }

    protected function getLabel($id)
    {
        return strval($id);
    }
}
