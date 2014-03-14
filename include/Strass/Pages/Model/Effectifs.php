<?php

class Strass_Pages_Model_Effectifs extends Strass_Pages_Model_Historique
{
  function fetch($annee = null)
  {
    return array('unite' => $this->unite,
		 /* Récursion = 1: seulement les sous-unités. */
		 'apps' => $this->unite->findAppartenances($annee, 1),
		 );
  }
}
