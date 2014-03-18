<?php

class Strass_Controller_Action_Helper_Journal extends Zend_Controller_Action_Helper_Abstract
{
  function direct($throw = true)
  {
    $slug = $this->getRequest()->getParam('journal');
    $t = new Journaux;
    try {
      $journal = $t->findBySlug($slug);
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw)
	throw new Strass_Controller_Action_Exception_Notice("Journal ".$slug." inexistant.");
      else
	return null;
    }

    $this->setBranche($journal);
    $this->_actionController->metas(array('DC.Title' => $journal->nom,
					  'DC.Subject' => 'journaux,journal,gazette,blog'));
    return $journal;
  }

  function setBranche($journal)
  {
    $unite = $journal->findParentUnites();
    $this->_actionController->_helper->Unite->liensConnexes($unite, 'index', 'unites');
    $this->_actionController->branche->append($journal->nom,
					      array('controller'=> 'journaux',
						    'action'	=> 'index',
						    'journal'	=> $journal->slug),
					      array(),
					      true);
  }
}
