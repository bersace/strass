<?php
function pack_unites($view, $parent, $unites)
{
  $list = $parent->addChild(new Wtk_List());
  foreach ($unites as $unite) {
    $link = $view->lienUnite($unite);
    $link->addFlags($unite->type);
    $item = $list->addItem($link);
    $item->addFlags($unite->type);
    pack_unites($view, $item, $unite->getSousUnites(false, false)); // sans récursion, unités ouverte
  }
}

$section = $this->document->addSection('unites', "Unités");
pack_unites($this, $section, $this->unites);
