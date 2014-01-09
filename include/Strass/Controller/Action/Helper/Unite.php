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

    /* beurk */
    $controller = $this->getRequest()->getParam('controller');
    switch($controller) {
    case 'unites':
      $action = 'index';
      break;
    case 'activites':
      $action = 'calendrier';
      break;
    case 'photos':
      $controller = 'unites';
      $action = 'index';
      break;
    default:
      $action = null;
      break;
    }

    $u = $unite;
    while ($u) {
      $this->_actionController->branche->insert(1,
						wtk_ucfirst($u->getName()),
						array('controller'=> $controller,
						      'action' => $action,
						      'unite' => $u->slug),
						array(),
						true);
      $u = $u->findParentUnites();
    }


    $page = Zend_Registry::get('page');
    $fn = wtk_ucfirst($unite->getFullname());
    $page->metas->set('DC.Title', $fn);
    $page->metas->set('DC.Creator', $fn);

    return $unite;
  }
}
