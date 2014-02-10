<?php

class Strass_Pages_Model_AccueilUnite extends Strass_Pages_Model_Historique
{
  function fetch($annee = NULL)
  {
    $u = $this->unite;
    $w = $u->getWiki();

    $unites = $u->getSousUnites(false, $annee);
    $photos = new Photos;

    $select = $photos->select()
      ->from('photo')
      ->where("strftime('%Y', activite.debut, '-8 months') = ?", strval($annee))
      ->limit(6);
    return array('unite' => $u,
		 'texte' => $w ? file_get_contents($w) : '',
		 'sousunites' => $unites,
		 'photos' => $photos->fetchPhotosAleatoiresForUnite($u, $select),
		 );
  }
}
