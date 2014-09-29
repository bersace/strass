<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Activite extends Zend_Controller_Action_Helper_Abstract
{
  function direct($throw = true)
  {
    $slug = $this->getRequest()->getParam('activite');
    $t = new Activites;

    try {
      $activite = $t->findBySlug($slug);
      $this->getRequest()->setParam('annee', $activite->getAnnee());
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw)
	throw new Strass_Controller_Action_Exception_NotFound("Activité ".$slug." inexistante.");
      else
	return null;
    }

    $this->setBranche($activite);

    return $activite;
  }

  function setBranche($activite)
  {
    $unites = $activite->findUnitesParticipantesExplicites();
    if ($unites->count() == 1) {
      $unite = $unites->current();
      $this->_actionController->_helper->Unite->liensConnexes($unite, 'calendrier');

      $this->_actionController->branche->append('Calendrier', array('annee' => false,
								    'activite' => false));
      $this->_actionController->branche->append($activite->getAnnee(),
						array('controller'=> 'activites',
						      'action'	=> 'calendrier',
						      'unite'	=> $unite->slug,
						      'annee' => $activite->getAnnee()),
						array(), true);
    }

    $this->_actionController->branche->append($activite->getIntituleCourt(),
					      array('controller'=> 'activites',
						    'action'	=> 'consulter',
						    'activite'	=> $activite->slug),
					      array(), true);
  }
}
