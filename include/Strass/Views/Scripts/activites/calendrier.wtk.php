<?php

class Strass_Views_PagesRenderer_Calendrier extends Strass_Views_PagesRenderer_Historique
{
  function render($annee = NULL, $data, $s) {
    extract($data);

    if (!$activites->count()) {
      $s->addParagraph()->addFlags('empty')
	->addInline("Aucune activité prévue en ".$annee);
      return $s;
    }

    if($future)
      $s->addDialog()->addFlags('warn')
	->addText("**La présence de chacun est primordiale** pour le bon déroulement ".
		  "des activités et pour la progression de tous.");

    $ss = $s->addSection('calendrier');

    $tam = new Wtk_Table_Model('id', 'slug', 'type', 'lieu', 'date', 'intitule');

    foreach($activites as $a) {
      if ($a->isFuture() && !$this->view->assert(null, $a, 'voir'))
	continue;

      $tam->append($a->id, $a->slug,  $a->getIntitule(), $a->lieu,
		   $a->getDate(), $a->getIntituleComplet());
    }
    $t = $ss->addTable($tam);

    $t->addNewColumn("Date", new Wtk_Table_CellRenderer_Text('text', 'date'));

    $c = new Wtk_Table_CellRenderer_Link('href', 'slug',
					 'label', 'type',
					 'tooltip', 'intitule');
    $t->addNewColumn("Activité", $c);
    // TODO: déterminer par activité si c'est future. Un
    // CellRendererLink spécialisé ferait l'affaire
    $url = $this->view->url(array('controller' => 'activites', 'action' => 'consulter', 'activite' => '%s'));
    $c->setUrlFormat(urldecode($url));

    return $s;
  }
}

$this->document->addPages(null, $this->model,
			 new Strass_Views_PagesRenderer_Calendrier($this));
