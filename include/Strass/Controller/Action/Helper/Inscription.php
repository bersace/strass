<?php

require_once 'Strass/Individus.php';

class Strass_Controller_Action_Helper_Inscription extends Zend_Controller_Action_Helper_Abstract
{
  function direct()
  {
    $id = $this->getRequest()->getParam('inscription');
    $t = new Appartenances;
    try {
      $app = $t->findOne($id);
    }
    catch (Strass_Db_Table_NotFound $e) {
      throw new Strass_Controller_Action_Exception_Notice("Inscription #".$id." inconnue.");
    }

    $this->setBranche($app);

    return $app;
  }

  function setBranche($app)
  {
    $this->_actionController->_helper->Individu->setBranche($app->findParentIndividus());
  }
}
