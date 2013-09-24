<?php

$this->document->addStyleComponents('trombi');
$s = $this->content;
$l = $s->addList();

$acl = Zend_Registry::get('acl');
$moi = Zend_Registry::get('individu');

foreach ($this->apps as $app) {
	$ind = $app->findParentIndividus();
	$i = $l->addItem();
	$i->addFlags("individu", $app->role, $app->type);
	$i->setId(wtk_strtoid($ind->getFullname()));

	// image
	$p = $i->addParagraph(" ")->addFlags('avatar');
	if (!($f = $ind->getImage()))
		$p->addFlags('inconnu');
	else
		$p->addImage($f, "Photo", $ind->getFullname());

	$ll = $i->addList();
	// nom prénom
	$ll->addItem($this->lienIndividu($ind))->addFlags('prenom-nom');
	// téléphone
	if ($acl->isAllowed($moi, $ind, 'voir') &&
	    $t = $ind->portable ? $ind->portable : $ind->fixe)
		$ll->addItem($t)->addFlags('telephone');
	// âge
	$ll->addItem("Né en ".$ind->getDateNaissance('%Y')." (".$ind->getAge()." ans)");
	// poste
	$ll->addItem(ucfirst($app->findParentRoles()->__toString()));

	if ($acl->isAllowed($moi, $ind, 'progression')) {
		if ($ind->perespi)
			$ll->addItem()->addFlags('perespi')
				->addInline("**Père spi :** ".$ind->perespi);

		if ($ind->parrain)
			$ll->addItem()->addFlags('parrain')
				->addInline("**Parrain :** ".$ind->parrain);
	}

	// progression
	if ($p = $ind->getProgression())
		$ll->addItem(ucfirst($p->findParentEtape()->titre))->addFlags('progression', $p->etape);
	// formation
	$fs = $ind->findFormation();
	if ($fs->count()) {
		$ii = $ll->addItem()->addFlags('formation');
		$formations = array();
		foreach($fs as $f) {
			$formations[] = $f->findParentDiplomes()->accr;
		}
		$ii->addChild(implode(', ', $formations));
	}
}