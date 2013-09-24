<?php

require_once 'Wtk.php';
require_once 'Knema/Users.php';
require_once 'Strass/Users.php';

/* nobody,nogroup = inconnu; */
class Knema_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
	protected	$http;
	protected	$db;
	protected	$sudo;

	public function routeStartup()
	{
		$acl = new Zend_Acl;
		Zend_Registry::set('acl', $acl);

		$config = new Knema_Config_Php('knema/site');
		try {
			$lifetime = $config->duree_connexion;
			Zend_Session::rememberMe($lifetime);
			Zend_Session::setOptions(array('cookie_path'	=> '/',
						       'cookie_lifetime'=> $lifetime,
						       'cache_expire'	=> $lifetime));
		} catch (Exception $e) {
		}

		// models formulaire
		$m = new Wtk_Form_Model('login');
		$i = $m->addString('username', "Identifiant");
		$m->addConstraintRequired($i);
		$i = $m->addString('password', "Mot de passe");
		$m->addConstraintRequired($i);
		$m->addNewSubmission('login', "Identifier");
		Zend_Registry::set('login_model', $m);

		$m = new Wtk_Form_Model('logout');
		$m->addBool('logout', "Déconnecter", TRUE);
		$m->addNewSubmission('logout', "Déconnecter");
		Zend_Registry::set('logout_model', $m);

		$db = Zend_Registry::get('db');

		// initialise la session.
		$auth = Zend_Auth::getInstance();
		$auth->getStorage();

		// DB AUTH
		$this->db = new Zend_Auth_Adapter_DbTable($db, 'users', 'username', 'password');

		// HTTP_AUTH
		$config = new Knema_Config_Php('knema/site');
		// Gestion du safe_mode avec realm modifié.
		$config = array('accept_schemes' => 'digest',
				'realm'	     => $config->realm,
				'digest_domains' => '/',
				'nonce_timeout'  => $config->duree_connexion);

		$this->http = new Zend_Auth_Adapter_Http($config);
    
		$db = new Knema_Auth_Adapter_Http_Resolver_DbTable($db, 'users', 'username', 'ha1');
		$this->http->setDigestResolver($db);

		// init le groupe admins.
		$admins = new Groups();
		$admins = $admins->find('admins')->current();

		$this->form();
		$this->getUser();

		// SUDO AUTH
		$this->sudo = new Knema_Auth_Adapter_Sudo(Zend_Registry::get('user'));
		Zend_Registry::set('user', $this->sudo->current);
		Zend_Registry::set('actual_user', $this->sudo->actual);
	}

	/* Authentifaction via formulaire
	 */
	private function form()
	{
		try {
			$auth = Zend_Auth::getInstance();
			$im = Zend_Registry::get('login_model');
			$om = Zend_Registry::get('logout_model');
			if ($im->validate()) {
				$username = $im->username;

				$this->db->setIdentity($username);
				// workaround SQLite not supporting MD5() treatment
				$this->db->setCredential(md5($im->password));
				$result = $auth->authenticate($this->db);

				if (!$result->isValid()) {
					switch($result->getCode()) {
					case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
						throw new Wtk_Form_Model_Exception('%s inexistant.',
										   $im->getInstance('username'));
						break;
					case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
						throw new Wtk_Form_Model_Exception("Mot de passe invalide.",
										   $im->getInstance('password'));
						break;
					default:
						throw new Wtk_Form_Model_Exception("Identification échouée");
						break;
					}
				}
			}
			else if ($om->validate()) {
				$auth->clearIdentity();
			}
		}
		catch (Wtk_Form_Model_Exception $e) {
			$auth->clearIdentity();
			$im->errors[] = $e;
		}
	}

	function sudo($target)
	{
		$auth = Zend_Auth::getInstance();
		$this->sudo->setTarget($target);

		$result = $auth->authenticate($this->sudo);

		Zend_Registry::set('user', $this->sudo->current);
		Zend_Registry::set('actual_user', $this->sudo->actual);
	}

	/* Authentification via HTTP.
	 */
	function http()
	{
		$m = Zend_Registry::get('logout_model');
		if (!$m->validate()) {
			$auth = Zend_Auth::getInstance();
			$this->http->setRequest($this->getRequest());
			$this->http->setResponse($this->getResponse());
			$result = $auth->authenticate($this->http);
		}

		return $this->getUser();
	}

	function getUser()
	{
		// authentification
		$auth = Zend_Auth::getInstance();

		if ($auth->hasIdentity()) {
			$id = $auth->getIdentity();
			if ($id instanceof User) {
				$username = null;
				$user = $id;
			}
			else
				$username = is_array($id) ? $id['username'] : $id;
		}
		else {
			$auth->clearIdentity();
			$username = 'nobody';
		}

		if ($username) {
			$users = new Users();
			$rows = $users->find($username);
			$user = $rows->current();
		}

		if (!$user) {
			$username = 'nobody';
			$user = $users->find($username)->current();
		}

		Zend_Registry::set('user', $user);

		return $user;
	}
}
