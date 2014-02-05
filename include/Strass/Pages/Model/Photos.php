<?php

class Strass_Pages_Model_Photos extends Strass_Pages_Model_Historique
{
  /* Devrait-on initialiser la liste des années à partir des années où
     on a des photos ? */

  function fetch($annee = NULL) {
    $activites = new Activites();
    $db = $activites->getAdapter();
    $select = $activites->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('activite')
      ->join('photo', 'photo.activite = activite.id', array())
      ->where("activite.debut > ?", $this->dateDebut($annee))
      ->where("activite.debut < ?", $this->dateFin($annee))
      ->where("activite.debut < STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP)")
      ->order('fin');
    return array('activites' => $activites->fetchAll($select));
  }
}
