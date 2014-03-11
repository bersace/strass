<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Album extends Zend_Controller_Action_Helper_Abstract
{
  function direct($throw = true)
  {
    $slug = $this->getRequest()->getParam('album');
    $t = new Activites;
    try {
      $activite = $t->findBySlug($slug);
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw)
	throw new Strass_Controller_Action_Exception_Notice("Album ".$slug." inexistant");
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
      $this->_actionController->_helper->Unite->setBranche($unite, 'index', 'photos');

      $urlOptions = array('controller'=> 'photos',
			  'action'	=> 'index',
			  'unite'	=> $unite->slug,
			  'annee' => $activite->getAnnee());
      $this->_actionController->branche->append($urlOptions['annee'],
						$urlOptions,
						array(),
						true);
    }

    $urlOptions = array('controller' => 'photos',
			'action' => 'consulter',
			'album'	=> $activite->slug);
    $this->_actionController->branche->append($activite->getIntituleCourt(),
					      $urlOptions, array(), true);
  }
}
