<?php

abstract class Strass_Pages_Model_Historique extends Wtk_Pages_Model_Assoc
{
  public $unite;

  function __construct(Unite $unite, $annee, $force=false)
  {
    $this->unite = $unite;
    $annees = $unite->getAnneesOuvertes();
    if ($force) {
      if (!array_key_exists($annee, $annees))
	$annees[$annee] = '##INCONNU##';
      ksort($annees);

      $keys = array_keys($annees);
      sort($keys);
      $precedente = current($keys) - 1;
      $annees[$precedente] = 'précédente';
      $suivante = end($keys) + 1;
      $annees[$suivante] = 'suivante';
      ksort($annees);

      /* Filtrer les années futures */
      foreach($annees as $a => $chef)
	if ($a > date('Y', time() - 120 * 24 * 3600))
	  unset($annees[$a]);
    }

    parent::__construct($annees, $annee);
  }

  // doublon de action/helper/annee…
  function dateDebut($annee)
  {
    return $annee.'-09-01';
  }

  function dateFin($annee)
  {
    return ($annee+1).'-08-31';
  }
}
