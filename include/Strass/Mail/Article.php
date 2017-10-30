<?php

class Strass_Mail_Article extends Strass_Mail
{
  function __construct($article) {
    parent::__construct("Nouvel article : ".$article->titre);

    $this->article = $article;
    $this->notifyChefsDe($article->findUnite());
  }

  function render()
  {
    $j = $this->article->findParentJournaux();
    $d = $this->getDocument();
    $d->addText("L'article suivant a été posté dans ".$j->nom.". ".
		"Vous êtes convié à la modérer.");
    $d->addSection(null, $this->article->titre)
      ->addText($this->article->getBoulet(true));
    $l = $d->addList();
    $l->addItem()->addLink($this->url(array('controller' => 'journaux',
					    'action' => 'ecrire',
					    'article' => $this->article->slug)),
			   "Valider cet article");
  }
}
