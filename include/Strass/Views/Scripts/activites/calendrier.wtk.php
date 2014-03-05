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

    $ss = $s->addSection('calendrier');
    $tam = new Wtk_Table_Model('id', 'slug', 'type', 'lieu', 'date', 'intitule');

    foreach($activites as $a) {
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
    if ($future) {
      $url = $this->view->url(array('controller' => 'activites', 'action' => 'consulter', 'activite' => '%s'));
    }
    else {
      $url = $this->view->url(array('controller' => 'photos', 'action' => 'consulter', 'album' => '%s'));
    }
    $c->setUrlFormat(urldecode($url));

    if($future)
      $ss->addText("**La présence de chacun est primordiale** pour le bon déroulement ".
		   "des activités et pour la progression de tous.");
    return $s;
  }
}

$this->document->addPages(null, $this->model,
			 new Strass_Views_PagesRenderer_Calendrier($this));
