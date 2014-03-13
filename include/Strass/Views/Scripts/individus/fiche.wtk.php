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

// CV scout
$s = $this->document->addSection('appartenances', "CV scout");
if ($this->apps->count()) {
  $this->document->addStyleComponents('unites');
  $m = new Wtk_Table_Model('unite_slug', 'unite_type', 'unite_nom', 'unite_lien',
			   'role', 'accr', 'acl',
			   'debut', 'fin');

  foreach($this->apps as $app) {
    $role = $app->findParentRoles();
    $unite = $app->findParentUnites();
    $url_unite =$this->url(array('controller' => 'unites', 'action' => 'contacts',
				 'unite' => $unite->slug, 'annee' => $app->getAnnee()), true);
    $m->append($unite->slug,
	       $unite->findParentTypesUnite()->slug,
	       $unite->getFullName(),
	       $url_unite,
	       array($role->slug, wtk_strtoid($app->titre)),
	       $app->getAccronyme(),
	       $role->acl_role,
	       strftime('%x', strtotime($app->debut)),
	       strftime('%x', strtotime($app->fin))
	       );
  }

  $t = $s->addTable($m, true, array('acl', 'role'));
  $config = Zend_Registry::get('config');
  $t->addFlags('effectifs', $config->system->mouvement, 'appartenances');
  $t->addNewColumn('Poste', new Wtk_Table_CellRenderer_Text('text', 'accr'));
  $t->addNewColumn('Unité', new Wtk_Table_CellRenderer_Link('href', 'unite_lien', 'label', 'unite_nom'));
  $t->addNewColumn('Début', new Wtk_Table_CellRenderer_Text('text', 'debut'));
  $t->addNewColumn('Fin', new Wtk_Table_CellRenderer_Text('text', 'fin'));
}
else {
  $s->addParagraph('Inscrit dans aucune unité !')->addFlags('empty');
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