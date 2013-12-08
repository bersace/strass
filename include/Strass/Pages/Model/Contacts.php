<?php

class Strass_Pages_Model_Contacts extends Wtk_Pages_Model_Assoc
{
  protected $_unite;
  protected $_cette_annee;
  protected $_calendrier;

  function __construct(Unite $unite, $annee)
  {
    $this->_unite = $unite;

    parent::__construct($unite->getAnneesOuverte(), $annee);
  }

  function fetch($annee = null)
  {
    $apps = $this->_unite->getApps($annee);

    // de même pour les sous-unités
    $sousunites = $this->_unite->getSousUnites(false, $annee);
    $ssapps = array();
    foreach($sousunites as $su) {
      switch($su->type) {
      case 'aines':
      case 'hp':
	// par défaut, on masques les effectifs de la HP car c'est
	// redondant par définition.
	$ssapps[$su->id] = array();
	break;
      default:
	$ssapps[$su->id] = $su->getApps($annee);
	break;
      }
    }
    return array('unite' => $this->_unite,
		 'sousunites' => $sousunites,
		 'apps' => $apps,
		 'sousapps' => $ssapps,
		 );
  }

  protected function isActuelle($annee)
  {
    return $annee == $this->_cette_annee;
  }

  function valid()
  {
    return in_array($this->pointer, $this->pages_id);
  }
}
