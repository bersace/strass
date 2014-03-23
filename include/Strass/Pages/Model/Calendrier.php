<?php

class Strass_Pages_Model_Calendrier extends Strass_Pages_Model_Historique
{
  function fetch($annee = NULL)
  {
    return array('activites' => $this->unite->findActivites($annee),
		 'annee' => $annee,
		 'unite' => $this->unite,
		 'future' => $annee >= date('Y', time()-243*24*60*60),
		 );
  }
}
