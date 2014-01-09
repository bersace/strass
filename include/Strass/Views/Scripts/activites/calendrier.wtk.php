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
    $tam = new Wtk_Table_Model('id', 'type', 'lieu', 'date', 'intitule');

    foreach($activites as $a) {
      $tam->append($a->id, wtk_ucfirst($a->getTypeName()), $a->lieu,
		   wtk_ucfirst($a->getDate(false, true)),
		   wtk_ucfirst($a->getIntitule(false)));
    }
    $t = $ss->addTable($tam);

    $t->addColumn(new Wtk_Table_Column("Date",
				       new Wtk_Table_CellRenderer_Text('text', 'date')));

    $c = new Wtk_Table_CellRenderer_Link('href', 'id',
					 'label', 'intitule');
    $t->addColumn(new Wtk_Table_Column("Activité", $c));
    // TODO: déterminer par activité si c'est future. Un
    // CellRendererLink spécialisé ferait l'affaire
    $url = $this->view->url(array('action' => $future ? 'consulter' : 'rapport',
				  'activite' => '%s'));
    $c->setUrlFormat(urldecode($url));

    if($future)
      $ss->addText("**La présence de chacun est primordiale** pour le bon déroulement ".
		   "des activités et pour la progression de tous.");
    return $s;
  }
}

$this->document->addPages(null, $this->model,
			 new Strass_Views_PagesRenderer_Calendrier($this));
