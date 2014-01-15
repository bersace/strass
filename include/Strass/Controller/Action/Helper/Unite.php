<?php

require_once 'Strass/Unites.php';

class Strass_Controller_Action_Helper_Unite extends Zend_Controller_Action_Helper_Abstract
{
  protected	$controller;

  function direct($slug = null, $throw = true)
  {
    $slug = $slug ? $slug : $this->getRequest()->getParam('unite');
    if ($slug)
      $unite = Unite::getInstance($slug);
    else {
      $unites = new Unites();
      $unite = $unites->getOuvertes("unite.parent IS NULL")->current();
    }

    if (!$unite)
      if ($throw)
	throw new Strass_Controller_Action_Exception_Notice("Unité ".$slug." inconnue");
      else
	return null;

    $this->liensConnexes($unite);

    $page = Zend_Registry::get('page');
    $fn = wtk_ucfirst($unite->getFullname());
    $page->metas->set('DC.Title', $fn);
    $page->metas->set('DC.Creator', $fn);

    return $unite;
  }

  protected function liensConnexes($unite)
  {
    /* hiérarchie des unités */
    $controller = $this->getRequest()->getParam('controller');
    $action = $this->getRequest()->getParam('action');
    $u = $unite;
    while ($u) {
      $this->_actionController->branche->insert(1,
						wtk_ucfirst($u->getName()),
						array('controller' => $controller,
						      'action' => $action,
						      'unite' => $u->slug),
						array(),
						true);
      $u = $u->findParentUnites();
    }

    // CONNEXES
    $connexes = $this->_actionController->connexes;
    $connexes->titre = $this->_actionController->view->lien(array('controller' => 'unites',
								  'action' => 'index'),
							    wtk_ucfirst($unite->getName()), true);

    $connexes->append("Photos",
		      array('controller' => 'photos',
			    'action' => 'index'));

    $connexes->append('Contacts',
		      array('controller' => 'unites',
			    'action' => 'contacts'),
		      array(null, $unite, 'contacts'));

    $connexes->append("Calendrier",
		      array('controller' => 'activites',
			    'action' => 'calendrier'),
		      array(null, $unite, 'calendrier'));

    $journal = $unite->findJournaux()->current();
    if ($journal)
      $connexes->append(wtk_ucfirst($journal->__toString()),
			array('controller' => 'journaux',
			      'action' => 'lire',
			      'journal' => $journal->id),
			array(), true);
  }
}
