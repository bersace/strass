<?php

$s = $this->content->addSection('activites', "Activités sans photos");

$l = $s->addChild(new Wtk_List());
foreach($this->activites as $act) {
  $i = $l->addItem($this->lienActivite($act));
}