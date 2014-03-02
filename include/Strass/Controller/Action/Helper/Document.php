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

    $this->_actionController->branche->append(wtk_ucfirst($d->titre));

    return $d;
  }
}
