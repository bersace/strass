<?php

class Strass_Addon_Liens extends Knema_Addon_Liens
{
	protected function lien($metas, array $urlOptions = array(), array $acl = array(), $reset = false)
	{
		if ($acl && $acl[0] == null)
			$acl[0] = Zend_Registry::get('individu');

		return parent::lien($metas, $urlOptions, $acl, $reset);
	}
}