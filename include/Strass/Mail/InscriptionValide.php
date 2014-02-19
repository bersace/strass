<?php

class Strass_Mail_InscriptionValide extends Strass_Mail
{
  function __construct($newuser, $message) {
    parent::__construct("Inscription validée");

    $this->newuser = $newuser;
    $this->message = $message;
  }

  function render()
  {
    $individu = $this->newuser->findParentIndividus();
    $this->addTo($individu->adelec, $individu->getFullname());

    $d = $this->getDocument();

    $d->addText($individu->getFullname().",\n\n".
		"Votre inscription a été validée !\n");
    if ($this->message)
      $d->addSection(null, 'Message du modérateur :')->addText($this->message);

    $l = $d->addList();
    $l->addItem()->addLink($this->url(array()),
			     "Accéder au site web");
    $l->addItem()->addLink($this->url(array('controller' => 'individus', 'action' => 'fiche')),
			   "Accéder à votre fiche");
  }
}
