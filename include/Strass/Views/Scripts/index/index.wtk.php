<?php
$this->document->addStyleComponents('vignette', 'signature', 'article');

$s = $this->content->addSection('accueil', "Bienvenue");
$s->addText($this->bienvenue);

function pack_unites($view, $list, $unites)
{
	$photos = new Photos;
	foreach ($unites as $unite) {
		$photo = $photos->findPhotoAleatoireUnite($unite);
		if (!$photo)
			$photo = $photos->findPhotoAleatoireUnite($unite->findParentUnites());

		$link = $view->vignettePhoto($photo,
					     wtk_ucfirst($unite),
					     array('controller' => 'unites',
						   'action' => 'accueil',
						   'unite' => $unite->id),
					     true);
		$link->addFlags($unite->type);
		$item = $list->addItem($link);
		$item->addFlags($unite->type);
		pack_unites($view, $list, $unite->getSousUnites(false, false)); // sans récursion, unités ouverte
	}
}

$section = $this->content->addSection('unites', "Les unités");
$list = $section->addList();
pack_unites($this, $list, $this->unites);

