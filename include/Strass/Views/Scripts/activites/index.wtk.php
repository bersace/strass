<?php

$s = $this->document->addSection('activites', "Prochaines activités");
$l = $s->addChild(new Wtk_List());

foreach($this->activites as $a) {
  $l->addItem($this->lienActivite($a));
}
