<?php

require_once 'Strass/Individus.php';

class Strass_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    public $http;
    public $db;
    public $sudo;

    public function initAcl()
    {
        $cache = Zend_Registry::get('cache');
        if (($acl = $cache->load('strass_acl')) === false) {
            $acl = new Strass_Acl;
            Zend_Registry::set('acl', $acl);

            if ($acl->hasRole('nobody')) {
                return;
            }

            $acl->add(new Zend_Acl_Resource('visiteur'));
            $acl->add(new Zend_Acl_Resource('membres'));
            $acl->add(new Zend_Acl_Resource('inscriptions'));
            $acl->add(new Zend_Acl_Resource('site'));

            $acl->addRole(new Zend_Acl_Role('nobody'));
            // groupes virtuels
            $acl->addRole(new Zend_Acl_Role('admins'));
            $acl->addRole(new Zend_Acl_Role('sachem'));
            $acl->addRole(new Zend_Acl_Role('membres'));

            $t = new Unites;
            $racines = $t->findRacines();
            foreach ($racines as $u)
                $u->initAclRoles($acl);

            $acl->allow('admins');
            $acl->allow('sachem', null, 'totem');
            $acl->allow('membres', 'membres', 'voir');

            $cache->save($acl, 'strass_acl');
        }
        else {
            Zend_Registry::set('acl', $acl, array(), null);
        }

        return $acl;
    }

    public function routeStartup()
    {
        $this->initAcl();

        $config = Zend_Registry::get('config');
        try {
            $lifetime = $config->system->duree_connexion;
            Zend_Session::setOptions(array(
                'cookie_path'	=> '/',
                'cookie_lifetime'=> $lifetime,
                'cache_expire'	=> $lifetime,
            ));
        } catch (Exception $e) {
            error_log((string) $e);
        }

        // models formulaire
        $m = new Wtk_Form_Model('login');
        $i = $m->addString('username', "Courriel");
        $m->addConstraintRequired($i);
        $i = $m->addString('password', "Mot de passe");
        $m->addConstraintRequired($i);
        $m->addNewSubmission('login', "Identifier");
        Zend_Registry::set('login_model', $m);

        $m = new Wtk_Form_Model('logout');
        $m->addNewSubmission('logout', "Déconnecter");
        Zend_Registry::set('logout_model', $m);

        $db = Zend_Registry::get('db');

        // initialise les méthodes d'authentifications.
        $auth = Zend_Auth::getInstance();

        // DB AUTH
        $this->db = new Strass_Auth_Adapter_DbTable($db, 'user', 'username', 'password');

        // HTTP_AUTH
        $config = array(
            'accept_schemes' => 'digest',
            'realm'	     => $config->system->realm,
            'digest_domains' => '/',
            'nonce_timeout'  => $config->system->duree_connexion);
        $this->http = new Zend_Auth_Adapter_Http($config);
        $resolver = new Strass_Auth_Adapter_Http_Resolver_DbTable($db, 'user', 'username', 'password');
        $this->http->setDigestResolver($resolver);

        // SUDO AUTH
        $this->sudo = new Strass_Auth_Adapter_Sudo;

        $this->sudo();
        $this->form();
        $this->getUser();
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
                $config = Zend_Registry::get('config');
                $this->db->setIdentity(array('username' => $username, 'realm' => $config->system->realm));
                $this->db->setCredential($im->password);
                $result = $auth->authenticate($this->db);

                if ($result->isValid()) {
                    return true;
                }
                else {
                    switch($result->getCode()) {
                    case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                        throw new Wtk_Form_Model_Exception('%s inexistant.',
                        $im->getInstance('username'));
                        break;
                    case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                        $router = Zend_Controller_Front::getInstance()->getRouter();
                        $url = $router->assemble(array('controller' => 'membres', 'action' => 'recouvrir'));
                        $url = "http://".$this->getRequest()->getServer('HTTP_HOST').$url;
                        throw new Wtk_Form_Model_Exception("Mot de passe invalide. [".$url." Oublié ?]",
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
            error_log("[AUTH][FORM] " . $e->getMessage());
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

            if (!$result->isValid())
                return false;
        }
        return true;
    }

    function sudo($target = null)
    {
        /* Fonction appelée dans deux cas : au début, pour recharger un
         * éventuel sudoer en cours, depuis la session, dans le registre. Et en
         * cas de sudo effectif. */
        $session = new Zend_Session_Namespace;
        $auth = Zend_Auth::getInstance();
        if ($target) {
            /* Changer l'identité */
            $this->sudo->target = $target;
            $result = $auth->authenticate($this->sudo);
            if ($result->isValid()) {
                $sudoer = Zend_Registry::get('user');
                $session->sudoer = $sudoer->getIdentity();
            }
        }

        if ($session->sudoer) {
            /* Restaurer l'objet sudoer depuis la session. */
            extract($session->sudoer);
            $t = new Users;
            $sudoer = $t->findByUsername($username);
            Zend_Registry::set('sudoer', $sudoer);
        }
    }

    function unsudo()
    {
        /* Restaure l'identité du sudoer et nettoye la session et le
         * registre. */

        $session = new Zend_Session_Namespace;
        $auth = Zend_Auth::getInstance();
        $this->sudo->target = Zend_Registry::get('sudoer');
        $result = $auth->authenticate($this->sudo);
        if ($result->isValid()) {
            unset($session->sudoer);
            $registry = Zend_Registry::getInstance();
            $registry->offsetUnset('sudoer');
        }
        return $result->isValid();
    }

    function getUser()
    {
        /* Récupère l'utilisateur depuis l'identité, et l'injecte dans le
         * registre. Initialise les ACL. */
        $auth = Zend_Auth::getInstance();
        $username = null;
        $user = null;

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();
            $username = $identity['username'];
        }

        if ($username) {
            $t = new Users;
            try {
                $user = $t->findByUsername($username);
                if (!Zend_Registry::isRegistered('sudoer'))
                    $user->last_login = new Zend_Db_Expr('CURRENT_TIMESTAMP');
                $user->save();
            }
            catch (Exception $e) {
                /* Ça arrive plutôt par un bug de développement, l'identité
                   dans la session n'existe plus. On déconnecte. */
                error_log($e->getMessage());
                $auth->clearIdentity();
            }
        }

        if (!$user) {
            $user = new Nobody;
        }

        $individu = $user->findParentIndividus();
        $acl = Zend_Registry::get('acl');
        if (!$acl->hasRole($user)) {
            $user->initAclRole($acl);
        }
        Zend_Registry::set('individu', $individu);
        Zend_Registry::set('user', $user);

        return $user;
    }
}
