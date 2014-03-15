<?php

$this->document->addFlags('sexe-'.$this->individu->sexe);

$s = $this->document->addSection('cartevisite', $this->individu->getFullName(false, false));
$s->addChild($this->vignetteIndividu($this->individu)->addFlags('nolabel'));

$l = $s->addList()->addFlags('infos');
if ($this->assert(null, $this->individu, 'totem'))
  $l->addItem()->addFlags('totem')->addRawText($this->individu->totem);
if ($this->etape) {
  $l->addItem()->addFlags('etape', $this->etape->slug)
    ->addInline("**".wtk_ucfirst($this->etape->titre)."**");
  $this->document->addFlags($this->etape->slug);
}

if ($this->individu->naissance) {
  $participe = $this->individu->sexe == 'h' ? 'Né' : 'Née';
  $l->addItem()->addRawText($participe." en ".
			    $this->individu->getDateNaissance('%Y').
			    " (".$this->individu->getAge()." ans)");
}

if ($adelec = $this->individu->adelec)
  $l->addItem()->addFlags('adelec')->addLink("mailto:".$adelec, $adelec);
if ($telephone = $this->individu->getTelephone())
  $l->addItem()->addFlags('telephone')->addLink("tel:".$telephone, $telephone);
if ($adresse = $this->individu->adresse)
  $l->addItem()->addFlags('adresse')->addRawText($adresse);


// DÉTAILS
$s = $this->document->addSection('details', "Détails");
$l = $s->addList();

$info = array();
$info['portable'] = "**Portable :** %s";
$info['fixe'] = "**Fixe :** %s";
if ($this->assert(null, $this->individu, 'voir-nom'))
  $info['numero'] = "**Numéro adhérent :** %s";

foreach($info as $k => $f) {
  if ($value = $this->individu->$k)
    $l->addItem()->addFlags($k)->addInline(sprintf($f, $value));
}

if ($this->assert(null, 'site') && $this->user->admin)
  $l->addItem()->addFlags('admin')->addStrong("Administrateur du site");
else if ($this->individu->isMember())
  $l->addItem()->addFlags('member')->addStrong("Membre");
if ($this->user->last_login)
  $l->addItem()->addFlags('last')->addInline("Connecté ".strftime("le %e-%m-%Y à %Hh%M"));
if ($this->individu->notes) {
  $s->addSection('notes')->addText($this->individu->notes);
}
else if (!count($l))
  $this->document->removeChild($s);


// CV SCOUT
$s = $this->document->addSection('cv', "CV scout");
if ($this->apps->count()) {
  $this->document->addStyleComponents('unites');
  $m = new Wtk_Table_Model('unite_slug', 'unite_type', 'unite_nom', 'unite_lien',
			   'role', 'accr', 'acl',
			   'debut', 'fin',
			   'url-editer', 'url-supprimer');

  foreach($this->apps as $app) {
    $role = $app->findParentRoles();
    $unite = $app->findParentUnites();
    $url_unite = $this->url(array('controller' => 'unites', 'action' => 'effectifs',
				  'unite' => $unite->slug, 'annee' => $app->getAnnee()), true);
    $url_editer = $this->url(array('controller' => 'individus', 'action' => 'reinscrire',
				   'inscription' => $app->id), true);
    $url_suppr = $this->url(array('controller' => 'individus', 'action' => 'desinscrire',
				   'inscription' => $app->id), true);
    $fin = $app->fin ? strftime('au %x', strtotime($app->fin)) : "à aujourd'hui";
    $m->append($unite->slug,
	       $unite->findParentTypesUnite()->slug,
	       $unite->getFullName(),
	       $url_unite,
	       array($role->slug, wtk_strtoid($app->titre)),
	       $app->getAccronyme(),
	       $role->acl_role,
	       strftime('du %x', strtotime($app->debut)), $fin,
	       $url_editer, $url_suppr
	       );
  }

  $t = $s->addTable($m, true, array('acl', 'role'));
  $config = Zend_Registry::get('config');
  $t->addFlags('effectifs', $config->system->mouvement, 'appartenances');
  $t->addNewColumn('Poste', new Wtk_Table_CellRenderer_Text('text', 'accr'));
  $t->addNewColumn('Unité', new Wtk_Table_CellRenderer_Link('href', 'unite_lien',
							    'label', 'unite_nom'), 'unite');
  $t->addNewColumn('Début', new Wtk_Table_CellRenderer_Text('text', 'debut'));
  $t->addNewColumn('Fin', new Wtk_Table_CellRenderer_Text('text', 'fin'));
  if ($this->assert(null, $this->individu, 'inscrire')) {
    $t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'url-editer',
							   'label', 'Éditer',
							   'flags', array('adminlink', 'editer')));
    $t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'url-supprimer',
							   'label', 'Supprimer',
							   'flags', array('adminlink', 'critical',
									  'supprimer')));
  }
}
else {
  $s->addParagraph('Inscrit dans aucune unité !')->addFlags('empty');
}
