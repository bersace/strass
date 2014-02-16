<?php

if (count($this->liens)) {
  $connexes = $this->parent->addSection($this->id, $this->titre);
  $list = $connexes->addList();
  foreach ($this->liens as $lien) {
    $url = $this->url($lien['urlOptions'], $lien['reset']);
    $i = $list->addItem();
    $i->addLink($url, new Wtk_Metas ($lien['metas']));
    $i->addFlags($lien['urlOptions'], split('/', $url), $lien['flags']);
    if (Zend_Controller_Front::getInstance()->getRequest()->REQUEST_URI == $url)
      $i->addFlags('current');
  }
}
