<?php

$this->document->addFlags('sexe-'.$this->individu->sexe);

$s = $this->document->addSection('cartevisite', $this->individu->getFullName(false, false));
$s->addChild($this->vignetteIndividu($this->individu)->addFlags('nolabel'));

$l = $s->addList()->addFlags('infos');
if ($this->assert(null, $this->individu, 'totem'))
    $l->addItem()->addFlags('totem')->addRawText($this->individu->totem);
if ($this->etape) {
    $l->addItem()->addFlags('etape', $this->etape->slug)
      ->addInline("**".$this->etape->titre."**");
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

foreach($info as $k => $f) {
    if ($value = $this->individu->$k)
        $l->addItem()->addFlags($k)->addInline(sprintf($f, $value));
}

if ($this->assert(null, 'site') && $this->user->admin)
    $l->addItem()->addFlags('admin')->addStrong("Administrateur du site");
else if ($this->individu->isMember())
    $l->addItem()->addFlags('member')->addStrong("Membre");
if ($this->user->last_login)
    $l->addItem()->addFlags('last')
      ->addInline("Connecté ".strftime(
          "le %e-%m-%Y à %Hh%M", strtotime($this->user->last_login)));
if ($this->individu->notes) {
    $s->addSection('notes')->addText($this->individu->notes);
}
else if (!count($l))
    $this->document->removeChild($s);


// CV SCOUT
$s = $this->document->addSection('cv', "CV scout");
if ($this->apps->count()) {
    $s->addChild($this->cvScout($this->apps, false)); /* Administrer */
}
else {
    $s->addParagraph('Inscrit dans aucune unité !')->addFlags('empty');
}
