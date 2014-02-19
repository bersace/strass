<?php

class Strass_Controller_Action_Helper_Commentaire extends Zend_Controller_Action_Helper_Abstract
{
  function direct($throw = true)
  {
    $id = $this->getRequest()->getParam('message');
    if (!$id)
	throw new Strass_Controller_Action_Exception_Notice("Message non spécifié.");

    $t = new Commentaires;
    try {
      return $t->findOne($id);
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw)
	throw new Strass_Controller_Action_Exception_NotFound("Commentaire #".$id." inexistant.");
      else
	return null;
    }
  }
}
