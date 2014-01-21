<?php

class Strass_Pages_Model_Calendrier extends Strass_Pages_Model_Historique
{
  function fetch($annee = NULL)
  {
    $u = $this->unite;
    $ta = new Activites();
    $db = $ta->getAdapter();
    $min = $this->dateDebut($annee).' 00:00';
    $max = $this->dateFin($annee).' 23:59';
    $select = $ta->select()
      ->setIntegrityCheck(false)
      ->from('activite')
      ->join('participation',
	     'participation.activite = activite.id'.
	     ' AND '.
	     $db->quoteInto("participation.unite = ?\n", $u->id),
	     array())
      ->where("debut >= ?", $min)
      ->where("debut <= ?", $max)
      ->order('activite.debut');
    $as = $ta->fetchAll($select);

    $future = $annee >= date('Y', time()-243*24*60*60);

    return array('activites' => $as,
		 'annee' => $annee,
		 'unite' => $u,
		 'future' => $future,
		 );
  }
}
