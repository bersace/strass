<?php

class Strass_Mail extends Knema_Mail
{
	function notifyAdmins()
	{
		$ti = new Individus();
		$db = $ti->getAdapter();
		$select = $db->select()
			->distinct()
			->from('individus')
			->join('membership',
			       'individus.username = membership.username'.
			       ' AND '.
			       "membership.groupname = 'admins'",
			       array());

		$admins = $ti->fetchSelect($select);
		$tos = array();
		foreach($admins as $admin)
			$this->addBcc($admin->adelec, $admin->getFullName(true));
	}

	function notifyChefs()
	{
		$tu = new Unites;
		$ti = new Individus;
		$s = $ti->select()
			->from('individus')
			->join('unites',
			       'unites.parent IS NULL',
			       array())
			->join('appartient',
			       'appartient.unite = unites.id'.
			       ' AND '.
			       "appartient.role = 'chef'".
			       ' AND '.
			       'appartient.fin IS NULL',
			       array())
			->where('individus.id = appartient.individu');
		$chefs = $ti->fetchSelect($s);

		$tos = array();
		foreach($chefs as $chef)
			$this->addBcc($chef->adelec, $chef->getFullName(false));
	}
}