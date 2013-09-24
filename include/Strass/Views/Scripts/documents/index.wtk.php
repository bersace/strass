<?php

$s = $this->content->addSection('documents');
$pre = NULL;
foreach($this->docs as $doc) {
	$unite = $doc->findParentUnites();
	if (!$pre || $unite->id != $pre->id) {
		$ss = $s->addSection($unite->id, "Document pour ".$unite->getFullname());
		$l = $ss->addChild(new Wtk_List());
		$pre = $unite;
	}
	$d = $doc->findParentDocuments();
	$l->addItem(new Wtk_Link($d->getUri(),
				 $d->titre));
}