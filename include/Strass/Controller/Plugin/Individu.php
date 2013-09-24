<?php

require_once 'Strass/Individus.php';

class Strass_Controller_Plugin_Individu extends Zend_Controller_Plugin_Abstract
{
	function routeStartup()
	{
		$this->findIndividu();
	}

	function findIndividu()
	{
		$user = Zend_Registry::get('user');

		if ($user instanceof FakeUser)
			$individu = $user->individu;
		else {
			$individus = new Individus();
			$where = $individus->getAdapter()->quoteInto('username = ?', $user->username);
			$individu = $individus->fetchRow($where);
		}
		Zend_Registry::set('individu', $individu);

		return $individu;
	}
}