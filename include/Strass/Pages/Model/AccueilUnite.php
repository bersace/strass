<?php

class Strass_Pages_Model_AccueilUnite extends Strass_Pages_Model_Historique
{
  function fetch($annee = NULL)
  {
    $u = $this->unite;
    $w = $u->getWiki();
    $unites = $u->findSousUnites($annee, false);
    return array('unite' => $u,
		 'texte' => $w ? file_get_contents($w) : '',
		 'sousunites' => $unites,
		 'photos' => $u->findPhotosAleatoires($annee),
		 );
  }
}
