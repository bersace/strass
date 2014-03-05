<?php

require_once 'Strass/Unites.php';

class Strass_Controller_Action_Helper_Unite extends Zend_Controller_Action_Helper_Abstract
{
	protected	$controller;

	function direct($id = null, $throw = true)
	{
		$id = $id ? $id : $this->getRequest()->getParam('unite');
		if ($id)
		  $unite = Unite::getInstance($id);
		else {
		  $unites = new Unites();
		  $unite = $unites->getOuvertes("unites.parent IS NULL")->current();
		}

		if (!$unite)
		  if ($throw)
		    throw new Knema_Controller_Action_Exception_Notice("Unité ".$id." inconnue");
		  else
		    return null;

		/* beurk */
		$controller = $this->getRequest()->getParam('controller');
		switch($controller) {
		case 'unites':
		  $action = 'accueil';
		  break;
		case 'activites':
		  $action = 'calendrier';
		  break;
		default:
		  $action = null;
		  break;
		}
		
		$controller = $this->getRequest()->getParam('controller');
		$controller = in_array($controller, array('unites', 'activites')) ? $controller : 'unites';
		$action = $action ? $action : ($controller == 'activites' ? 'calendrier' : 'accueil');
		$this->_actionController->branche->append(wtk_ucfirst($unite->getFullname()),
							  array('controller'	=> $controller,
								'action'	=> $action,
								'unite'		=> $id),
							  array(),
							  true);
		$page = Zend_Registry::get('page');
		$fn = wtk_ucfirst($unite->getFullname());
		$page->metas->set('DC.Title', $fn);
		$page->metas->set('DC.Creator', $fn);

		return $unite;
	}
}
