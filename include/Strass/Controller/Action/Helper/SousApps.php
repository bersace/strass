<?php

class Strass_Controller_Action_Helper_SousApps extends Zend_Controller_Action_Helper_Abstract
{
	function direct($ssunites, $annee)
	{
		$ssapps = array();
		foreach($ssunites as $su) {
			switch($su->type) {
			case 'aines':
			case 'hp':
				// par défaut, on masques les effectifs de la HP car c'est
				// redondant par définition.
				$ssapps[$su->id] = array();
				break;
			default:
				$ssapps[$su->id] = $su->findAppartenances($annee);
				break;
			}
		}
		return $ssapps;
	}
}
