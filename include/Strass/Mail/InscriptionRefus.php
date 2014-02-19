<?php

class Strass_Mail_InscriptionRefus extends Strass_Mail
{
  function __construct($inscription, $message) {
    parent::__construct("Inscription refusée");

    $this->inscription = $inscription;
    $this->message = $message;
  }

  function render()
  {
    $this->addTo($this->inscription->adelec, $this->inscription->getFullname());

    $d = $this->getDocument();

    $d->addText($this->inscription->getFullname().",\n\n".
		"Votre inscription a été refusée !\n");

    if ($this->message)
      $d->addSection(null, 'Message du modérateur :')
	->addFlags('message')
	->addText($this->message);
  }
}
