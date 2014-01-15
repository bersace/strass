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


  // helper ?
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
    $connexes->append('Accueil',
		      array('controller' => 'unites',
			    'action' => 'index'));

    $connexes->append("Photos",
		      array('controller' => 'photos',
			    'action' => 'index'));

    $connexes->append('Contacts',
		      array('controller' => 'unite',
			    'action' => 'contacts'),
		      array(null, $unite, 'contacts'));

    $connexes->append("Calendrier",
		      array('controller' => 'activites',
			    'action' => 'calendrier'),
		      array(null, $unite, 'calendrier'));

    // ACTIONS
    $actions = $this->_actionController->actions;
    $actions->append("Modifier",
		     array('unite' => $unite->slug,
			   'action' => 'modifier'),
		     array(null, $unite));

    $actions->append("Détruire",
		     array('unite' => $unite->slug,
			   'action' => 'detruire'),
		     array(null, $unite));


    $soustypename = $unite->getSousTypeName();
    if (!$unite->isTerminale() && $soustypename)
      $actions->append(array('label' => "Fonder une ".$soustypename),
			     array('action' => 'fonder',
				   'parente' => $unite->slug),
			     array(null, $unite));

    if ($unite->findParentTypesUnite()->findRoles()->count() != 0) {
      $actions->append(array('label' => "Compléter l'effectif"),
			     array('controller' => 'unites',
				   'action' => 'historique',
				   'unite' => $unite->slug),
			     array(null, $unite));

      $actions->append(array('label' => "Inscrire un nouveau"),
			     array('controller' => 'inscription',
				   'action' => 'nouveau',
				   'unite' => $unite->slug),
			     array(null, $unite));
    }

    $actions->append("Enregistrer la progression",
			   array('action' => 'progression',
				 'unite' => $unite->slug),
			   array(null, $unite));

    if (!$unite->isFermee())
      $actions->append("Fermer l'unité",
			     array('action' => 'fermer'),
			     array(null, $unite));

    // journal d'unité
    $journal = $unite->findJournaux()->current();
    if ($journal)
      $connexes->append(wtk_ucfirst($journal->__toString()),
			array('controller' => 'journaux',
			      'action' => 'lire',
			      'journal' => $journal->id),
			array(), true);

    else if (!$unite->isTerminale())
      $actions->append("Fonder le journal d'unité",
		       array('controller' => 'journaux',
			     'action' => 'fonder'),
		       array(null, $unite, 'fonder-journal'));

  }
}
