<?php

$s = $this->content->addSection('liens');
$l = $s->addChild(new Wtk_List());
foreach($this->liens as $lien) {
  $p = new Wtk_Paragraph(new Wtk_Inline($lien->description));
  $p->addFlags('description');
  $l->addItem(new Wtk_Container(new Wtk_Link($lien->url, $lien->nom), $p));
}