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
		$individu = $ti->find($id)->current();

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

	function auth()
	{
		$fc = Zend_Controller_Front::getInstance();
		$plugin = $fc->getPlugin('Strass_Controller_Plugin_Individu');
		$individu = $plugin->findIndividu();
		Zend_Registry::set('individu', $individu);
		return $individu;
	}
}
