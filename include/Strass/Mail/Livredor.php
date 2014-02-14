<?php

class Strass_Mail_Livredor extends Strass_Mail
{
  function __construct($message) {
    parent::__construct("Nouveau message sur le livre d'or");

    $this->message = $message;
  }

  function render()
  {
    $d = $this->getDocument();
    $d->level+=2;
    $d->addParagraph("Cher administrateur,");
    $d->addParagraph($this->message->auteur." a posté un message sur le livre d'or, ".
		     "vous êtes invité à le ",
		     new Wtk_Link($this->url(array('controller' => 'livredor', 'action' => 'moderer')),
				  "modérer"),
		     ".");
    $s = $d->addSection(null, 'Message de '.$this->message->auteur);
    $s->addText($this->message->contenu);
  }
}
