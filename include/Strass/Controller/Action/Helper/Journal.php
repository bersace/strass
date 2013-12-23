<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Journal extends Zend_Controller_Action_Helper_Abstract
{
	function direct($throw = true, $reset = true)
	{
		$id = $this->getRequest()->getParam('journal');
		$journaux = new Journaux();
		$journal = $journaux->find($id)->current();
		if (!$journal && $throw)
			throw new Strass_Controller_Action_Exception_Notice("Journal ".$id." inexistant.");

		if ($journal) {
			if ($reset) {
				$controller = $this->getRequest()->getParam('controller');
				$this->_actionController->branche->append(wtk_ucfirst($journal->nom),
									  array('controller'	=> $controller,
										'action'	=> 'lire',
										'journal'	=> $id),
									  array(),
									  true);
			}
			else 
				$this->_actionController->branche->append(wtk_ucfirst($journal->nom));
		}

		return $journal;
	}
}
