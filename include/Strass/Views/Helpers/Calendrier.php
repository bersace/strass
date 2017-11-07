<?php

class Strass_View_Helper_Calendrier
{
  protected	$view;
  public static $headers = array('accr' => '',
				 'prenom-nom' => 'Nom',
				 'adelec' => 'Adélec',
				 'fixe' => 'Fixe',
				 'portable' => 'Portable',
				 'adresse' => 'Adresse',
				 'naissance' => 'Naissance',
				 );

  public function setView($view)
  {
    $this->view = $view;
  }

  function calendrier($activites, $annee)
  {
    if (!$activites->count()) {
      $p = new Wtk_Paragraph();
      $p->addFlags('empty')
	->addInline("Aucune activité prévue en ".$annee);
      return $p;
    }

    $s = new Wtk_Section('calendrier');
    $tam = new Wtk_Table_Model('id', 'slug', 'type', 'lieu', 'date', 'intitule');

    foreach($activites as $a) {
      if ($a->isFuture() && !$this->view->assert(null, $a, 'consulter'))
	continue;

      $tam->append($a->id, $a->slug,  $a->getIntitule(), $a->lieu,
		   $a->getDate(), $a->getIntituleComplet());
    }
    $t = $s->addTable($tam);

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
