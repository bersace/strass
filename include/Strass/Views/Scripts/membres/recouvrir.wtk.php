<?php

if ($this->finish) {
    $this->document->setTitle(null);
    $d = $this->document->addDialog("Nouveau mot de passe défini");
    $d->addText(<<<EOS

Votre nouveau mot de passe est défini. Vous pouvez vous identifier avec \
votre adresse {$this->individu->adelec} et ce mot de passe.

Bonne visite !
EOS
    );
}
else if ($this->set) {
    $f = $this->document->addChild(new Wtk_Form($this->set));
    $f->addPassword('nouveau', 24);
    $f->addPassword('confirmation', 24);
    $f->addForm_ButtonBox()->addForm_Submit($this->set->getSubmission('enregistrer'));
}
else if ($this->send) {
    $f = $this->document->addChild(new Wtk_Form($this->send));
    $f->addSection()->addFlags('info')
      ->addText("Un courriel va vous être envoyé avec un lien temporaire ".
      "vers la page pour définir un nouveau mot de passe.");
    $f->addEMail('adelec', 24);
    $f->addForm_ButtonBox()->addForm_Submit($this->send->getSubmission('envoyer'));
}