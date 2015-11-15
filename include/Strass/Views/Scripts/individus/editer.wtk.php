<?php

$this->document->setTitle(new Wtk_Container(new Wtk_Inline("Modifier la fiche de "),
$this->lienIndividu($this->individu)));
$f = $this->document->addForm($this->model);

$g = $f->addForm_Fieldset('État civil');
try {
    // ces champs ne sont pas forcément présent, soit parce que
    // seul l'admin peut le corriger, soit parce qu'il faut être
    // sachem pour y avoir accès.
    $g->addEntry('prenom', 24);
    $g->addEntry('nom', 24);
    $g->addDate('naissance', '%e/%m/%Y');
    $g->addSelect('sexe');
} catch(Exception $e){}

$g->addFile('image');

// SCOUTISME
$g = $f->addForm_Fieldset("Scoutisme");

try {
    $g->addEntry('totem', 24);
} catch(Exception $e){}

try {
    $g->addSelect('etape', true);
} catch(Exception $e){}

// suppression si vide.
if (!$g->count())
    $f->removeChild($g);

// contacts
$g = $f->addForm_Fieldset('Contacts');
$g->addEntry('adresse', 32, 2);
$g->addEntry('fixe', 14);
$g->addEntry('portable', 14);
if ($this->individu->isMember()) {
    $url = $this->url(
        array('controller' => 'membres',
        'action' => 'parametres',
        'membre' => $this->individu->findUser()->username), true, true);
    /* Simuler un champ de formulaire, mais c'est un lien */
    $g->addSection()->addFlags('control', 'entry')
      ->addSpan()->addFlags('control', 'entry', 'input')
      ->addInline("[".$url." Éditer l'adresse électronique]");
}
else {
    $g->addEMail('adelec', 24);
}

$g = $f->addForm_Fieldset('Notes');
$g->addEntry('notes', 64, 8)->useLabel(false);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('valider'));
