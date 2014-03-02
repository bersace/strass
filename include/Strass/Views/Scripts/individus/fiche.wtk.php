<?php

if ($this->etape)
  $this->document->addFlags($this->etape->slug);

$this->document->addFlags('sexe-'.$this->individu->sexe);

$s = $this->document->addSection('informations', "Informations personnelles");

if ($i = $this->individu->getImage())
  $s->addParagraph()->addFlags('avatar')->addImage($i, "Photo", $this->individu->getFullname());

$l = $s->addList();
if ($this->individu->naissance) {
  $participe = $this->individu->sexe == 'h' ? 'Né' : 'Née';
  $l->addItem()->addRawText($participe." en ".
			    $this->individu->getDateNaissance('%Y').
			    " (".$this->individu->getAge()." ans)");
}
$info = array('adelec'		=> "**Adélec :** [mailto:%s %s]",
	      'portable'	=> "**Téléphone portable :** %s",
	      'fixe'		=> "**Téléphone fixe :** %s",
	      'adresse'		=> "**Addresse :** %s",
	      );

$acl = Zend_Registry::get('acl');
if ($this->etape)
  $info['etape'] = "**".wtk_ucfirst($this->etape->titre)."**";
if ($acl->isAllowed(null, $this->individu, 'totem'))
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

if ($this->chef && $this->user->admin)
  $l->addItem()->addFlags('admin')->addStrong("Administrateur du site");
else if ($this->individu->isMember()) {
  $l->addItem()->addFlags('member')->addStrong("Membre");
}
if ($this->user->last_login)
  $l->addItem()->addFlags('last')->addInline("**Dernière connexion :** ".strftime("le %e-%m-%Y à %Hh%M"));

// notes
if ($this->individu->notes) {
  $ss = $s->addSection('notes', "Notes");
  $ss->addText($this->individu->notes);
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
    $u = $app->findParentUnites();
    $l->addItem(new Wtk_Container($this->lienUnite($u, wtk_ucfirst($app->getShortDescription()),
						   array('annee' => $app->getAnnee())),
				  " en ".strtok($app->debut, "-")."-".strtok($app->fin, "-")));
  }
}

// commentaires
if ($this->commentaires->count()) {
  $s = $this->document->addSection('commentaires', "Derniers commentaires de photos");
  $l = $s->addList();
  foreach($this->commentaires as $commentaire) {
    $i =  $l->addItem();
    $i->addChild($this->lienPhoto($commentaire->findPhoto()));
    $i->addChild(" le ".strftime("%e-%m-%Y", strtotime($commentaire->date)).".");
  }
}

// articles
if ($this->articles->count()) {
  $s = $this->document->addSection('articles', "Derniers articles");
  $l = $s->addList();
  foreach($this->articles as $article) {
    $i = $l->addItem();
    $i->addChild($this->lienArticle($article));
    $i->addRawText(" le ".strftime("%e-%m-%Y", strtotime($article->getDate())."."));
  }
}