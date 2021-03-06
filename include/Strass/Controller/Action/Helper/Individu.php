<?php

require_once 'Strass/Individus.php';

class Strass_Controller_Action_Helper_Individu extends Zend_Controller_Action_Helper_Abstract
{
  function direct($throw = true)
  {
    $slug = $this->getRequest()->getParam('individu');
    $t = new Individus;
    try {
      $individu = $t->findBySlug($slug);
    }
    catch (Strass_Db_Table_NotFound $e) {
      $adresse = $this->getRequest()->getParam('adresse');
      try {
	$individu = $t->findByEMail($adresse);
      }
      catch (Strass_Db_Table_NotFound $e) {
	if ($throw)
	  throw new Strass_Controller_Action_Exception_NotFound("Individu ".$slug." inconnu.");
	else
	  return null;
      }
    }

    $this->setBranche($individu);

    return $individu;
  }

  function setBranche($individu)
  {
    $this->_actionController->branche->append($individu->getFullname(),
					      array('controller'	=> 'individus',
						    'action'	=> 'fiche',
						    'individu'	=> $individu->slug),
					      array(), true);
  }
}
