<?php

class Strass_Mail_Inscription extends Strass_Mail
{
  function __construct($inscription) {
    parent::__construct("Nouvelle inscription : ".$inscription->getFullname());

    $this->inscription = $inscription;
    $this->notifyAdmins();
    $this->notifyChefs();
  }

  function render()
  {
    $d = $this->getDocument();
    $d->addText("Chers administrateurs,\n\n".
		$this->inscription->getFullname()." (".
		strftime('%e/%m/%Y', strtotime($this->inscription->naissance)).
		") a soumis son inscription :");

    $s = $d->addSection('presentation')->addFlags('message');
    $s->addText($this->inscription->presentation);

    $l = $d->addList();
    $l->addItem()->addLink($this->url(array('controller' => 'membres',
					    'action' => 'valider',
					    'adresse' => $this->inscription->adelec)),
			   "Modérer cette inscription");
    $l->addItem()->addLink($this->url(array('controller' => 'membres',
					    'action' => 'inscriptions')),
			   "Voir les inscriptions en attentes");
  }
}
