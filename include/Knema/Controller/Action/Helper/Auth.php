<?php

require_once 'Knema/Users.php';

/* nobody,nogroup = inconnu; */
class Knema_Controller_Action_Helper_Auth extends Zend_Controller_Action_Helper_Abstract
{
	protected $plugin;

	public function init()
	{
		$fc = Zend_Controller_Front::getInstance();
		$this->plugin = $fc->getPlugin('Knema_Controller_Plugin_Auth');
	}

	function sudo($target)
	{
		return $this->plugin->sudo($target);
	}

	/* Authentification via HTTP.
	 */
	function http()
	{
		return $this->plugin->http();
	}

	function direct()
	{
		return $this->plugin->getUser();
	}
}
