<?php
$a = $this->activite;
$s = $this->content->addSection('activite');

// INFORMATIONS GÉNÉRALES
$ss = $s->addSection('informations', 'Informations générales');
$intro = $ss->addText("Ce".($a->intitule{0} == 's' ? 'tte' : '')." ".$a->getIntitule(false, false).
		      " se déroul".($a->isFuture()? 'er' : '')."a ".$a->getDate().($a->lieu ? " à ".$a->lieu : "").". ");
if ($a->isFuture()) {
	$intro->append("**Rendez-vous à ".strftime("%Hh%M", strtotime($a->debut)).($a->depart ? " ".$a->depart : "")."**, ".
		       "retour ".strftime("vers %Hh%M", strtotime($a->fin)).($a->retour ? " ".$a->retour : "").". ".
		       "Veuillez lire attentivement les informations qui suivent.");
 }

// À APPORTER
$l = new Wtk_List();
if ($a->cotisation)
	$l->addItem()->addRawText($a->cotisation." € de cotisation ;");

foreach($a->findApports() as $apport)
	$l->addItem(new Wtk_Inline($apport->item." ;"));

if ($l->count()) {
	$ss = $s->addSection('apporter', 'À apporter');
	$ss->addChild($l);
 }


// PIÈCES JOINTES
if ($this->documents->count()) {
	$ss = $s->addSection('piecesjointes', "Pièces-jointes");
	$l = $ss->addChild(new Wtk_List());
	foreach($this->documents as $docact)
		$l->addItem($this->lienDocument($docact->findParentDocuments()));
 }

// MESSAGE

if ($a->message) {
	$ss = $s->addSection('message', "Informations complémentaires");
	$ss->addText($a->message);
 }

// UNITÉ PARTICIPANTES
$unites = $a->getUnitesParticipantes();

if ($unites->count()) {
	$titre = $a->isFuture() ? "Unités participantes" : "Rapports des unités";
	$ss = $s->addSection('participants', $titre);

	$l = $ss->addChild(new Wtk_List());


	// Suivant que l'activité soit future ou passée, on ira à la page de
	// l'unité (effectif) ou au livre d'or.
	if ($a->isFuture()) {
		$url = array('annee' => $a->getAnnee());
	}
	else {
		$url = array('controller' => 'activites',
			     'action' => 'rapport',
			     'activite' => $a->id);
	}

	foreach($unites as $unite) {
		$l->addItem($this->lienUnite($unite, null, $url));
	}

 }

// ANTI ABSENTS
if ($a->isFuture())
	$s->addParagraph()
		->addFlags('remarque')
		->addInline("**La présence de chacun est primordiale** pour le bon déroulement de cette activité.");
