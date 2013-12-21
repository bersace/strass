<?php

class Strass_Pages_Model_Photos extends Strass_Pages_Model_Historique
{
  /* Devrait-on initialiser la liste des années à partir des années où
     on a des photos ? */

  function fetch($annee = NULL) {
    $activites = new Activites();
    $db = $activites->getAdapter();
    $select = $db->select()
      ->distinct()
      ->from('activites')
      ->join('photos', 'photos.activite = activites.id', array())
      ->where("activites.debut > ?", $this->dateDebut($annee))
      ->where("activites.debut < ?", $this->dateFin($annee))
      ->where("activites.debut < STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP)")
      ->order('fin');
    return array('activites' => $activites->fetchSelect($select));
  }
}
