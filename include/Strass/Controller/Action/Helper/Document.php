<?php

require_once 'Strass/Documents.php';

class Strass_Controller_Action_Helper_Document extends Zend_Controller_Action_Helper_Abstract
{
  function direct($throw = true)
  {
    $slug = $this->getRequest()->getParam('document');
    $t = new Documents;
    try {
      $d = $t->findBySlug($slug);
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw)
	throw new Strass_Controller_Action_Exception_NotFound("Document ".$slug." inconnu.");
      else
	return null;
    }

    $this->setBranche($d);

    return $d;
  }

  function setBranche($d)
  {
    try {
      $u = $d->findUnite();
      $this->_actionController->_helper->Unite->liensConnexes($u, 'index', 'documents');
    }
    catch (Strass_Db_Table_NotFound $e) {}

    $this->_actionController->branche->append('Documents', array('action' => 'index',
								 'unite' => null, 'document' => null,));
    $this->_actionController->branche->append($d->titre);
  }
}
