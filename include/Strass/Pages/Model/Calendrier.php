<?php

class Strass_Pages_Model_Calendrier extends Strass_Pages_Model_Historique
{
  /* On devrait initialiser la liste des années à partir des activités
     et nos des inscriptions. Actuellement, on génère des liens vers
     des pages vides… */

  function fetch($annee = NULL) {
    $u = $this->unite;
    $ta = new Activites();
    $db = $ta->getAdapter();
    $min = $this->dateDebut($annee).' 00:00';
    $max = $this->dateFin($annee).' 23:59';
    $select = $db->select()
      ->from('activites')
      ->join('participe',
	     'participe.activite = activites.id'.
	     ' AND '.
	     $db->quoteInto('participe.unite = ?', $u->id),
	     array())
      ->where("debut >= ?", $min)
      ->where("debut <= ?", $max)
      ->where("fin >= ?", $min)
      ->where("fin <= ?", $max)
      ->order('activites.debut');
    $as = $ta->fetchSelect($select);

    $future = $annee >= date('Y', time()-243*24*60*60);
      
    return array('activites' => $as,
		 'annee' => $annee,
		 'unite' => $u,
		 'future' => $future,
		 );
  }
}