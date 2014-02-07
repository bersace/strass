<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Journal extends Zend_Controller_Action_Helper_Abstract
{
  function direct($throw = true)
  {
    $slug = $this->getRequest()->getParam('journal');
    $journaux = new Journaux();
    try {
      $journal = $journaux->findBySlug($slug);
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw)
	throw new Strass_Controller_Action_Exception_Notice("Journal ".$slug." inexistant.");
      else
	return null;
    }

    $this->setBranche($journal);
    return $journal;
  }

  function setBranche($journal)
  {
    $this->_actionController->branche->append(wtk_ucfirst($journal->nom),
					      array('controller'=> 'journaux',
						    'action'	=> 'lire',
						    'journal'	=> $journal->slug),
					      array(),
					      true);
  }
}
