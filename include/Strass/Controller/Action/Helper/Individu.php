<?php

require_once 'Strass/Individus.php';

class Strass_Controller_Action_Helper_Individu extends Zend_Controller_Action_Helper_Abstract
{
	function param()
	{
		$args = func_get_args();
		return call_user_func_array(array($this, 'direct'), $args);
	}

	function direct($throw = true, $reset = true)
	{
		$id = $this->getRequest()->getParam('individu');
		$ti = new Individus;
		$individu = $ti->findBySlug($id);

		if (!$individu && $throw)
			throw new Strass_Controller_Action_Exception_Notice("Individu ".$id." inconnu.");

		if ($individu) {
			if ($reset) {
				$this->_actionController->branche->append(wtk_ucfirst($individu->getFullname()),
									  array('controller'	=> 'individus',
										'action'	=> 'voir',
										'individu'	=> $id),
									  array(),
									  true);
			}
			else 
				$this->_actionController->branche->append(wtk_ucfirst($individu->getFullname()));
		}

		return $individu;
	}
}
