<?php

class Strass_Views_PagesRenderer_Calendrier extends Strass_Views_PagesRenderer_Historique
{
  function render($annee = NULL, $data, $s) {
    extract($data);

    $s->addChild($this->view->Calendrier($activites, $annee));

    if($future && $activites->count())
      $s->addDialog()->addFlags('warn')
	->addText("**La présence de chacun est primordiale** pour le bon déroulement ".
		  "des activités et pour la progression de tous.");

    return $s;
  }
}

$this->document->addFlags('printable');
$this->document->addPages(null, $this->model,
			 new Strass_Views_PagesRenderer_Calendrier($this));
