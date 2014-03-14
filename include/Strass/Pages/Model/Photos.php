<?php

class Strass_Pages_Model_Photos extends Strass_Pages_Model_Historique
{
  /* Devrait-on initialiser la liste des années à partir des années où
     on a des photos ? */

  function fetch($annee = NULL) {
    return array('albums' => $this->unite->findAlbums($annee));
  }
}
