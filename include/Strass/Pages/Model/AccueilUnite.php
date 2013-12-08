<?php

class Strass_Pages_Model_AccueilUnite extends Strass_Pages_Model_Historique
{
  function fetch($annee = NULL)
  {
    $u = $this->unite;
    $w = $u->getWiki();

    if (!$u->parent) {
      $unites = [$u];
    }
    else {
      $unites = $u->getSousUnites(false, $annee);
    }

    return array('unite' => $u,
		 'texte' => $w ? file_get_contents($w) : '',
		 'sousunites' => $unites,
		 );
  }
}
