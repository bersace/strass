<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Rubrique extends Zend_Controller_Action_Helper_Abstract
{
	function direct($throw = true)
	{
		$req = $this->getRequest();
		$id = $req->getParam('rubrique');
		$j = $req->getParam('journal');
		$rubriques = new Rubriques();
		$rubrique = $rubriques->find($id, $j)->current();
		if (!$rubrique && $throw)
			throw new Strass_Controller_Action_Exception_Notice("Rubrique ".$id." inconnue");
		if ($rubrique) {
			$this->_actionController->branche->append(wtk_ucfirst($rubrique->nom),
								  array('controller'	=> 'journaux',
									'action'	=> 'lister',
									'journal'	=> $j,
									'rubrique'	=> $id),
								  array(),
								  true);
		}

		return $rubrique;
	}
}
