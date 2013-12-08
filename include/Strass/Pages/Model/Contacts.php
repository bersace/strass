<?php

class Strass_Pages_Model_Contacts extends Strass_Pages_Model_Historique
{
  function fetch($annee = null)
  {
    $apps = $this->unite->getApps($annee);

    // de même pour les sous-unités
    $sousunites = $this->unite->getSousUnites(false, $annee);
    $ssapps = array();
    foreach($sousunites as $su) {
      switch($su->type) {
      case 'aines':
      case 'hp':
	// par défaut, on masques les effectifs de la HP car c'est
	// redondant par définition.
	$ssapps[$su->id] = array();
	break;
      default:
	$ssapps[$su->id] = $su->getApps($annee);
	break;
      }
    }

    return array('unite' => $this->unite,
		 'sousunites' => $sousunites,
		 'apps' => $apps,
		 'sousapps' => $ssapps,
		 );
  }
}
