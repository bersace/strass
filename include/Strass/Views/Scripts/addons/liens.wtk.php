<?php

if (count($this->liens)) {
    $connexes = $this->parent->addSection($this->id, $this->titre);
    $list = $connexes->addList();
    $current_uri = Zend_Controller_Front::getInstance()->getRequest()->REQUEST_URI;
    foreach ($this->liens as $lien) {
        if (is_array($lien['urlOptions']))
            $url = $this->url($lien['urlOptions'], $lien['reset'], false, $lien['anchor']);
        else
            $url = $lien['urlOptions'];
        $i = $list->addItem();
        $i->addLink($url, new Wtk_Metas($lien['metas']));
        $i->addFlags($lien['urlOptions'], split('/', $url), $lien['flags']);
        if ($current_uri == $url)
            $i->addFlags('current');
    }
}
