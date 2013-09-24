<?php

$this->document->addStyleComponents('sousunites');
$s = $this->content->addSection('sousunites', "Les ".$this->soustype);
$l = $s->addList();

$acl = Zend_Registry::get('acl');
$moi = Zend_Registry::get('individu');

foreach ($this->sousunites as $su) {
	$i = $l->addItem();
	$i->addFlags("sousunite", $su->type, $su->id);
	// image
	if ($f = $su->getImage())
		$i->addImage($f, "Photo", $su->getFullname());

	$ll = $i->addList();
	// Nom
	$ll->addItem($this->lienUnite($su,
				      wtk_ucfirst($su->getFullname().' '.$su->extra)));

	// chef
	$apps = $su->getApps($this->annee);
	$chef = null;
	foreach($apps as $app)
		if ($app->role == 'chef') {
			$chef = $app;
			break;
		}

	if ($chef)
		$ll->addItem(new Wtk_Strong($app->findParentRoles()->getAccronyme().' : '),
			     $this->lienIndividu($chef->findParentIndividus()))
			->addFlags('chef');
	// effectifs
	$ll->addItem(count($apps)." membres.")->addFlags('membres');

	// prochaine activité
	if ($this->acl->isAllowed($this->moi, $su, 'calendrier')) {
		$part = $su->getProchainesParticipations(1, true)->current();
		if ($part)
			$ll->addItem(new Wtk_Strong("Prochaine activité : "),
				     $this->lienActivite($part->findParentActivites()));
	}
}
