<?php
function pack_unites($view, $parent, $unites)
{
  $list = $parent->addChild(new Wtk_List());
  foreach ($unites as $unite) {
    $link = $view->lienUnite($unite, null, array('annee' => $unite->getDerniereAnnee()));
    $link->addFlags($unite->type);
    $item = $list->addItem($link);
    $item->addFlags($unite->type);
    pack_unites($view, $item, $unite->getSousUnites(false));
  }
}

$section = $this->content->addSection('unites', "Unités fermées");
pack_unites($this, $section, $this->unites);
