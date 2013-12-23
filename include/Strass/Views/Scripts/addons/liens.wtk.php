<?php

  // menu
if (count($this->liens)) {
	$connexes = $this->addons->addSection ($this->id, $this->titre);
	$list = $connexes->addList();
	foreach ($this->liens as $lien) {
		$url = $this->url($lien['urlOptions'], $lien['reset']);
		$i = $list->addItem(new Wtk_Link($url, new Wtk_Metas ($lien['metas'])));
		$i->addFlags(explode('/', $url));
	}
 }
