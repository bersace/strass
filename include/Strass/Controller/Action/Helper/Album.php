<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Album extends Zend_Controller_Action_Helper_Abstract
{
  function direct($slug = null, $throw = true)
  {
    $slug = $slug ? $slug : $this->getRequest()->getParam('album');
    $activites = new Activites();
    $activite = $activites->findBySlug($slug);

    if (!$activite)
      if ($throw)
	throw new Strass_Controller_Action_Exception_Notice("Album ".$slug." inexistante.");
      else
	return null;

    $this->setBranche($activite);

    return $activite;
  }

  function setBranche($activite)
  {
    $unites = $activite->getUnitesParticipantesExplicites();
    if ($unites->count() == 1) {
      $unite = $unites->current();
      $urlOptions = array('controller'=> 'photos',
			  'action'	=> 'index',
			  'unite'	=> $unite->slug);
      $this->_actionController->branche->append(wtk_ucfirst($unite->getName()),
						$urlOptions,
						array(),
						true);

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
    $this->_actionController->branche->append(wtk_ucfirst($activite->getIntitule(false)),
					      $urlOptions,
					      array(),
					      true);
  }
}
