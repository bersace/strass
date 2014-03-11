<?php

class Strass_Pages_Model_Archives extends Strass_Pages_Model_Historique
{
  function fetch($annee = NULL)
  {
    $u = $this->unite;
    $w = $u->getWiki();
    $unites = $u->findSousUnites($annee, false);
    return array('unite' => $u,
		 'unites' => $unites,
		 'anciens' => array(),
		 );
  }
}
