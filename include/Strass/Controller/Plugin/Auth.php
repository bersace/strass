<?php

require_once 'Wtk.php';
require_once 'Strass/Individus.php';

class Strass_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
  protected $http;
  protected $db;
  protected $sudo;

  public function routeStartup()
  {
    $acl = new Zend_Acl;
    Zend_Registry::set('acl', $acl);

    if (!$acl->hasRole('individus')) {
      $acl->addRole(new Zend_Acl_Role('individus'));
      // groupes virtuels
      $acl->addRole(new Zend_Acl_Role('admins'));
      $acl->allow('admins');
      $acl->addRole(new Zend_Acl_Role('sachem'));
      $acl->allow('sachem', null, 'totem');
      $acl->addRole(new Zend_Acl_Role('members'));
      $nobody = new Nobody;
      $acl->addRole($nobody);
    }

    $config = new Strass_Config_Php('strass');
    try {
      $lifetime = $config->site->duree_connexion;
      Zend_Session::rememberMe($lifetime);
      Zend_Session::setOptions(array('cookie_path'	=> '/',
				     'cookie_lifetime'=> $lifetime,
				     'cache_expire'	=> $lifetime));
    } catch (Exception $e) {}

    // models formulaire
    $m = new Wtk_Form_Model('login');
    $i = $m->addString('username', "Courriel");
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

    // DB AUTH
    $this->db = new Strass_Auth_Adapter_DbTable($db, 'user', 'username', 'password');

    // HTTP_AUTH
    $config = array('accept_schemes' => 'digest',
		    'realm'	     => $config->site->realm,
		    'digest_domains' => '/',
		    'nonce_timeout'  => $config->site->duree_connexion);

    $this->http = new Zend_Auth_Adapter_Http($config);

    $db = new Strass_Auth_Adapter_Http_Resolver_DbTable($db, 'user', 'username', 'password');
    $this->http->setDigestResolver($db);

    $this->form();
    $this->getUser();

    // SUDO AUTH
    $this->sudo = new Strass_Auth_Adapter_Sudo(Zend_Registry::get('user'));
    Zend_Registry::set('user', $this->sudo->current);
    Zend_Registry::set('actual_user', $this->sudo->actual);
  }

  /* Authentification via formulaire */
  private function form()
  {
    try {
      $auth = Zend_Auth::getInstance();
      $im = Zend_Registry::get('login_model');
      $om = Zend_Registry::get('logout_model');

      if ($im->validate()) {
	$username = $im->username;
	// Regénérer le digest à partir du username original
	$config = new Strass_Config_Php('strass');
	$this->db->setIdentity(array('username' => $username, 'realm' => $config->site->realm));
	$this->db->setCredential($im->password);
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

  /* Authentification via HTTP Digest. */
  function http()
  {
    $m = Zend_Registry::get('logout_model');
    if (!$m->validate()) { // Tenir compte de la déconnexion
      $auth = Zend_Auth::getInstance();
      $this->http->setRequest($this->getRequest());
      $this->http->setResponse($this->getResponse());
      $result = $auth->authenticate($this->http);
    }
    return $this->getUser();
  }

  function sudo($target)
  {
    $auth = Zend_Auth::getInstance();
    $this->sudo->target = $target;
    $result = $auth->authenticate($this->sudo);
    Zend_Registry::set('user', $this->sudo->current);
    Zend_Registry::set('actual_user', $this->sudo->actual);
  }

  function getUser()
  {
    $auth = Zend_Auth::getInstance();
    $username = null;
    $user = null;

    if ($auth->hasIdentity()) {
      $identity = $auth->getIdentity();
      $username = $identity['username'];
    }

    if ($username) {
      $t = new Users();
      $user = $t->findByUsername($username);
    }

    if (!$user) {
      $user = new Nobody();
    }

    Zend_Registry::set('user', $user);
    Zend_Registry::set('individu', $user->findParentIndividus());

    return $user;
  }
}
