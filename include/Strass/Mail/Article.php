<?php

class Strass_Mail_Article extends Strass_Mail
{
  function __construct($article) {
    parent::__construct("Nouvel article : ".$article->titre);

    $this->article = $article;
    $auteur = $article->findAuteur();
    $this->setFrom($auteur->adelec, $auteur->getFullname());
    $this->notifyChefsDe($article->findParentJournaux()->findParentUnites());
  }

  function render()
  {
    $d = $this->getDocument();
    $d->addText("L'article suivant a été posté dans ".$j->nom.". ".
		"Vous êtes convié à la modérer.");
    $s = $d->addSection(null, $data['titre']);
    $s->addText($this->article->boulet);
    $l = $d->addList();
    $l->addItem()->addLink($this->url(array('controller' => 'journaux',
					    'action' => 'ecrire',
					    'article' => $this->article->slug)),
			   "Éditer ou publier cet article");
  }
}
