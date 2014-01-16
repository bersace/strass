<?php

class Strass_Pages_Model_Contacts extends Strass_Pages_Model_Historique
{
  function fetch($annee = null)
  {
    return array('unite' => $this->unite,
		 'apps' => $this->unite->getApps($annee, true),
		 );
  }
}
