<?php

$this->document->addStyleComponents('vignette');
$s = $this->content->addSection("rapports");
foreach($this->participations as $participation)
{
	$activite = $participation->findParentActivites();

	$ss = $s->addSection(null, $this->lienActivite($activite,
						       null, null, null, // label, action, controller
						       $participation->findParentUnites(),
						       true));
	$ss->addFlags('activite', $participation->unite);

	$vignette = $ss->addSection(null, null);
	$vignette->addFlags('vignette');
	$vignette->addChild($this->vignettePhoto($this->photos[$activite->id]));

	$boulet = $ss->addSection(null, null);
	$boulet->addFlags('boulet');
	$boulet->addText($participation->boulet ? $participation->boulet : wtk_first_words($participation->rapport));

	if ($participation->rapport) {
		$lien = $this->lienActivite($activite, 'Lire la suite…', 'rapport', 'activites', false);
		$ss->addParagraph($lien)->addFlags('suite');
					  
	}
	else if (!$participation->boulet)
		$ss->addText("//Pas de rapport sur cette activité. //");
}