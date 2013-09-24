<?php
  /*
   * Format un n° de téléphone.
   */
class Strass_Controller_Action_Helper_Telephone extends Zend_Controller_Action_Helper_Abstract
{
	function direct($telephone)
	{
		if (preg_match_all("`(\d)`", $telephone, $chiffres)) {
			$telephone = implode('', $chiffres[1]);
			$telephone = trim(preg_replace("`(\d{2})`", "\$1 ", $telephone));
		}
		else
			$telephone = '';

		return $telephone;
	}
}
