<?php

class Strass_Controller_Action_Helper_Annee extends Zend_Controller_Action_Helper_Abstract
{
  function direct($fallback = true)
  {
    $annee = $this->getRequest()->getParam('annee');
    $annee = $annee ? $annee : ($fallback ? $this->cetteAnnee() : null);
    return intval($annee);
  }

  function setBranche($annee)
  {
    if (!$annee)
      return;

    $this->_actionController->branche->append($annee, array('annee' => $annee));
    return $annee;
  }

  static function dateDebut($annee)
  {
    /* on commence parfois fin août */
    return $annee.'-08-30';
  }

  static function dateFin($annee)
  {
    /* on termine parfois début septembre */
    return ($annee+1).'-09-03';
  }

  static function cetteAnnee()
  {
    return strftime('%Y', strtotime('-8 month'));
  }
}
