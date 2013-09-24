<?php

$this->document->addStyleComponents('table');

$s = $this->content->addSection('valider', "Valider une inscription");

$i = $this->inscription;
$f = $s->addForm($this->model);

$enum = array('accepter' => 'acceptée',
	      'refuser' => 'refusée');
if ($this->verdict)
	if ($this->precedent)
		$p = $f->addParagraph("L'inscription de ",
				      $this->lienIndividu($this->precedent),
				      " a été ".$enum[$this->verdict]." sur le site.")->addFlags('info');
	else
		$p = $f->addParagraph("Inscription précédente ".$enum[$this->verdict]." !")->addFlags('warn');

$f->addParagraph(new Wtk_Inline("Veillez à ne valider que les inscriptions ".
				"dont vous êtes **sûr** de l'authenticité."))->addFlags('warn');
$g = $f->addForm_Fieldset("Fiche d'inscription");

if ($this->individu)
	$g->addParagraph($this->lienIndividu($this->individu),
			  " a sa fiche dans la base.")->addFlags('info');
else
	$g->addParagraph("Vous pouvez corriger des erreurs dans la fiche d'inscription.")->addFlags('info');

$g->addEntry('prenom')->setReadonly($this->individu);
$g->addEntry('nom')->setReadonly($this->individu);
$g->addEntry('username', 24);
$g->addEntry('adelec', 24);

$ss = $g->addSection(null, "Message");
//$ss->level = $s->level+1;
$ss->addText($i->message);

$ss = $g->addSection(null, "Scoutisme dans le groupe");

if (!$this->individu)
	$ss->addParagraph("Cette personne n'est inscrite dans aucune unité".
			  ($i->scoutisme ? "" : " et n'a fourni aucun détails sur son historique dans le groupe").
			  ".")->addFlags('warn');

if ($i->scoutisme)
	$ss->addText("||~ Unité ||~ Poste ||~ Début ||~ Fin||\n".$i->scoutisme);
 else if ($this->individu) {
	 $l = $ss->addList();
	 foreach($this->individu->findAppartenances() as $a) {
		 if ($a->fin) {
			 $l->addItem(new Wtk_Container(new Wtk_RawText(ucfirst($a->findParentRoles()->__toString())." dans "),
						       $this->lienUnite($a->findParentUnites(),
									null, array('annee' => $a->getDebut('%Y'))),
						       new Wtk_RawText(" du ".$a->getDebut()." au ".$a->getFin())));
		 }
		 else {
			 $l->addItem(new Wtk_Container(new Wtk_RawText(ucfirst($a->findParentRoles()->__toString())." dans "),
						       $this->lienUnite($a->findParentUnites()),
						       new Wtk_RawText(" depuis le ".$a->getDebut())));
		 }
	 }
 }

$g = $f->addForm_Fieldset("Validation");

// $c = $g->addCheck('verdict');
$c = $g->addSelect('verdict');
$g->addEntry('message', 64, 4);
try {
	$g->addSelect('unite');
}catch(Exception $e){}

$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addForm_Submit($this->model->getSubmission('valider'));
