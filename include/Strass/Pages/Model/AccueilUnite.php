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
      ->from('photos')
      ->join('activites', 'activites.id = photos.activite', array())
      ->where("strftime('%Y', activites.debut, '-8 months') = ?", strval($annee))
      ->limit(4);
    return array('unite' => $u,
		 'texte' => $w ? file_get_contents($w) : '',
		 'sousunites' => $unites,
		 'photos' => $photos->fetchPhotosAleatoiresForUnite($u, $select),
		 );
  }
}
