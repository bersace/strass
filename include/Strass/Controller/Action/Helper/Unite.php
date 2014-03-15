<?php

require_once 'Strass/Unites.php';

class Strass_Controller_Action_Helper_Unite extends Zend_Controller_Action_Helper_Abstract
{
  function direct($throw = true)
  {
    $slug = $this->getRequest()->getParam('unite');
    $t = new Unites;
    try {
      if ($slug)
	$unite = $t->findBySlug($slug);
      else {
	$unite = $t->findRacine();
      }
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw) {
	$message = $slug ? "Unité ".$slug." inconnue" : "Pas d'unité !";
	throw new Strass_Controller_Action_Exception_Notice($message);
      }
      else
	return null;
    }

    $this->liensConnexes($unite);

    $page = Zend_Registry::get('page');
    $fn = $unite->getFullname();
    if (!$page->metas->get('DC.Title'))
      $page->metas->set('DC.Title', $fn);
    $page->metas->set('DC.Creator', $fn);

    return $unite;
  }

  function setBranche($unite, $action=null, $controller=null)
  {
    /* hiérarchie des unités */
    if (!$controller)
      $controller = $this->getRequest()->getParam('controller');
    if (!$action)
      $action = $this->getRequest()->getParam('action');

    $us = array();
    $u = $unite;
    while ($u) {
      array_unshift($us, $u);
      $u = $u->findParentUnites();
    }

    foreach($us as $u) {
      $this->_actionController->branche->append($u->getName(),
						array('controller' => $controller,
						      'action' => $action,
						      'unite' => $u->slug),
						array(),
						true);
    }
  }

  function liensConnexes($unite, $action=null, $controller=null)
  {
    $this->setBranche($unite, $action, $controller);

    // CONNEXES
    $connexes = $this->_actionController->connexes;
    $url = $this->_actionController->_helper->Url('index', 'unites', null, null, true);
    $connexes->titre = new Wtk_Link($url, $unite->getTypeName());

    $connexes->append("Photos",
		      array('controller' => 'photos',
			    'action' => 'index',
			    'unite' => $unite->slug),
		      array(), true);

    $connexes->append('Effectifs',
		      array('controller' => 'unites',
			    'action' => 'effectifs',
			    'unite' => $unite->slug),
		      array(null, $unite, 'effectifs'),
		      true);

    $connexes->append("Calendrier",
		      array('controller' => 'activites',
			    'action' => 'calendrier',
			    'unite' => $unite->slug),
		      array(null, $unite, 'calendrier'),
		      true);

    $connexes->append("Documents",
		      array('controller' => 'documents',
			    'action' => 'index',
			    'unite' => $unite->slug),
			array(), true);

    if (!$unite->isTerminale() && !$unite->findParentTypesUnite()->virtuelle)
      $connexes->append("Archives",
			array('controller' => 'unites',
			      'action' => 'archives',
			      'unite' => $unite->slug),
			array(), true);

    $journal = $unite->findJournaux()->current();
    if ($journal)
      $connexes->append($journal->__toString(),
			array('controller' => 'journaux',
			      'action' => 'index',
			      'journal' => $journal->slug),
			array(), true);
  }
}
