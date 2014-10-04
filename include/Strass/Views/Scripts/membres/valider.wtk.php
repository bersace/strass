<?php

if (!$this->model) {
  $this->document->addParagraph("Aucune inscription à valider")->addFlags('empty');
  return;
}

$f = $this->document->addForm($this->model);

$f->addEntry('prenom', 24);
$f->addEntry('nom', 24);
if ($this->individu) {
  $f->addParagraph()->addFlags('warn')
    ->addInline("Êtes-vous **sûr** de l'authenticité de cette inscription ?");
  $f->addSection('vignette')
    ->addChild($this->vignetteIndividu($this->individu));
  $f->addSelect('fiche', false);
}

$s = $f->addSection('presentation');
$s->addParagraph($this->inscription->adelec)->addFlags('auteur');
$s->addText($this->inscription->presentation);

$f->addEntry('message', 48, 4);

$b = $f->addForm_ButtonBox();
$b->addForm_Submit($this->model->getSubmission('accepter'));
$b->addForm_Submit($this->model->getSubmission('refuser'))->addFlags('critical');
