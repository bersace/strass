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
	  $this->_actionController->branche->append($annee, array('annee' => $annee));
	  return $annee;
	}

	function dateDebut($annee)
	{
		return $annee.'-09-01';
	}

	function dateFin($annee)
	{
		return ($annee+1).'-08-31';
	}

	function cetteAnnee()
	{
		return strftime('%Y', time()-8*30*24*60*60);
	}
}
