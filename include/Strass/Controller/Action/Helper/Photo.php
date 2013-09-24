<?php

require_once 'Strass/Photos.php';

class Strass_Controller_Action_Helper_Photo extends Strass_Controller_Action_Helper_Activite
{
	function direct($throw = true)
	{
		$a = parent::direct(null, $throw, true,
				    array('controller'  => 'photos',
					  'action'	=> 'consulter'));
		$photos = new Photos();
		$p = $photos->find($this->getRequest()->getParam('photo'), $a->id)->current();
		if (!$p && $throw)
			throw new Zend_Controller_Exception("Photo invalide");

		$annee = $a->getAnnee();
		$this->_actionController->branche->insert(-1, $annee,
							  array('controller' => 'photos',
								'action' => 'index',
								'annee' => $annee),
							  array(),
							  true);


		if ($p)
			$this->_actionController->branche->append(wtk_ucfirst($p->titre),
								  array('controller'	=> 'photos',
									'action'	=> 'voir',
									'activite'	=> $a->id,
									'photo'		=> $p->id),
								  array(),
								  true);

		return array($a, $p);
	}
}
