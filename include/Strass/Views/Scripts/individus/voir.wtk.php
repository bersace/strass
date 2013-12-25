<?php

$s = $this->document->addSection('informations', "Informations personnelles");


if ($i = $this->individu->getImage())
	$s->addParagraph()->addFlags('avatar')->addImage($i, "Photo", $this->individu->getFullname());

$l = $s->addList();
$l->addItem(new Wtk_RawText("Né en ".$this->individu->getDateNaissance('%Y')." (".$this->individu->getAge()." ans)"));
$info = array('adelec'		=> "**Adélec :** [mailto:%s %s]",
	      'portable'	=> "**Téléphone portable :** %s",
	      'fixe'		=> "**Téléphone fixe :** %s",
	      'adresse'		=> "**Addresse :** %s",
	      );

$acl = Zend_Registry::get('acl');
$ind = Zend_Registry::get('user');
if ($acl->isAllowed($ind, $this->individu, 'totem'))
	$info['totem'] = '**Totem :** %s';

if ($this->chef) {
	$info['numero']	 = "**Numéro adhérent :** %s";
 }


foreach($info as $k => $f) {
	if ($this->individu->$k)
		$l->addItem(new Wtk_Inline(str_replace(array('%s', "\n"),
						       array($this->individu->$k, " – "),
						       $f)))->addFlags($k);
}

if ($this->chef && $this->isadmin)
	$l->addItem()->addFlags('admin')->addStrong("Administrateur du site");

// notes
if ($this->individu->notes) {
	$ss = $s->addSection('notes', "Notes");
	$ss->addText($this->individu->notes);
 }

// progression
if ($this->progression->count()) {
	$s = $this->document->addSection('progression', 'Progression');
	$l = $s->addList();

	foreach($this->progression as $progression) {
		$etape = $progression->findParentEtape();
		$l->addItem('A '.$etape->participe_passe.' '.ucfirst($etape->titre).
			    ($progression->date ? ' le '.strftime('%e-%m-%Y', strtotime($progression->date)) : '').
			    ($progression->lieu ? ' à '.$progression->lieu : null).
			    '.')->addFlags('progression', $progression->etape);
	}
 }

// formation
if ($this->formation->count()) {
	$s = $this->document->addSection('formation', 'Formation');
	$l = $s->addList();
	foreach($this->formation as $formation) {
		$diplome = $formation->findParentDiplomes();
		$l->addItem(wtk_ucfirst($diplome->titre).
			    ' '.$diplome->getBranche().
			    ($formation->date ? ' le '.strftime('%e-%m-%Y', strtotime($formation->date)) : '').
			    '.');
	}
 }

// activité
if ($this->appactives->count()) {
	$s = $this->document->addSection('activites', "Actuellement");
	$l = $s->addList();
	foreach($this->appactives as $app) {
		$l->addItem(new Wtk_Container(new Wtk_RawText(ucfirst($app->findParentRoles()->__toString())." dans "),
					      $this->lienUnite($app->findParentUnites()),
					      new Wtk_RawText(" depuis le ".$app->getDebut())));
	}
 }

// historique
if ($this->historique->count()) {
	$s = $this->document->addSection('historique', "Historique");
	$l = $s->addList();
	foreach($this->historique as $app) {
		$l->addItem(new Wtk_Container(new Wtk_RawText(ucfirst($app->findParentRoles()->__toString())." dans "),
					      $this->lienUnite($app->findParentUnites(),
							       null, array('annee' => $app->getAnnee())),
					      new Wtk_RawText(" du ".$app->getDebut()." au ".$app->getFin())));
	}
 }

// commentaires

if ($this->commentaires->count()) {
	$s = $this->document->addSection('commentaires', "Derniers commentaires de photos");
	$l = $s->addList();
	foreach($this->commentaires as $commentaire)
		$l->addItem(new Wtk_Container($this->lienPhoto($commentaire->findParentPhotos()),
					      new Wtk_RawText(" le ".strftime("%e-%m-%Y", strtotime($commentaire->date)).".")));
 }

// articles
if ($this->articles->count()) {
	$s = $this->document->addSection('articles', "Derniers articles");
	$l = $s->addList();
	foreach($this->articles as $article) {
		$l->addItem(new Wtk_Container($this->lienArticle($article),
					      new Wtk_RawText(" le ".strftime("%e-%m-%Y", strtotime($article->date)).".")));
	}
 }