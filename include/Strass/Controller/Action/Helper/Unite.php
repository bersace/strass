<?php

require_once 'Strass/Unites.php';

class Strass_Controller_Action_Helper_Unite extends Zend_Controller_Action_Helper_Abstract
{
  function direct($slug = null, $throw = true)
  {
    $slug = $slug ? $slug : $this->getRequest()->getParam('unite');
    $t = new Unites();
    try {
      if ($slug)
	$unite = $t->findBySlug($slug);
      else {
	$unite = $this->racine();
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
    $fn = wtk_ucfirst($unite->getFullname());
    if (!$page->metas->get('DC.Title'))
      $page->metas->set('DC.Title', $fn);
    $page->metas->set('DC.Creator', $fn);

    return $unite;
  }

  function racine()
  {
    $t = new Unites();
    $s = $t->select()->where('unite.parent IS NULL');
    return $t->fetchOne($s);
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
      $this->_actionController->branche->append(wtk_ucfirst($u->getName()),
						array('controller' => $controller,
						      'action' => $action,
						      'unite' => $u->slug),
						array(),
						true);
    }
  }

  protected function liensConnexes($unite)
  {
    $this->setBranche($unite);

    // CONNEXES
    $connexes = $this->_actionController->connexes;
    $connexes->titre = $this->_actionController->view->lien(array('controller' => 'unites',
								  'action' => 'index'),
							    wtk_ucfirst($unite->getTypeName()), true);

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

    $connexes->append("Documents",
		      array('controller' => 'documents',
			    'action' => 'index'));

    $journal = $unite->findJournaux()->current();
    if ($journal)
      $connexes->append(wtk_ucfirst($journal->__toString()),
			array('controller' => 'journaux',
			      'action' => 'lire',
			      'journal' => $journal->slug),
			array(), true);
  }
}
