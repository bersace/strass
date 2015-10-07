<?php

require_once 'Zend/Auth/Result.php';

class Strass_Auth_Adapter_Sudo implements Zend_Auth_Adapter_Interface
{
    public $unsudo = false;
    public $target;

    function authenticate()
    {
        /* On stocke dans la session l'identifiant de l'utilisateur original */
        $session = new Zend_Session_Namespace;

        if ($session->sudoer) {
            if ($this->unsudo) {
                $sudoer = Zend_Registry::get('sudoer');
                $identity = $sudoer->getIdentity();

                $registry = Zend_Registry::getInstance();
                $registry->offsetUnset('sudoer');

                $session->sudoer = null;
            }
            else {
                /* Charger l'utilisateur original */
                extract($session->sudoer);
                $t = new Users;
                $sudoer = $t->findByUsername($username);
                Zend_Registry::set('sudoer', $sudoer);

                /* NOOP */
                $identity = Zend_Registry::get('user')->getIdentity();
            }
        }
        else if ($this->target) {
            /* sudo effectif */
            $sudoer = Zend_Registry::get('user');
            Zend_Registry::set('sudoer', $sudoer);
            /* Changer l'identité */
            $identity = $this->target->getIdentity();
            $session->sudoer = $sudoer->getIdentity();
        }
        else {
            /* On considère l'authentification comme un succès s'il n'y a
               pas de sudo, ou le sudo est déjà dans la session. C'est un
               NOOP */
            $current = Zend_Registry::get('user');
            if ($current->isMember())
                $identity = $current->getIdentity();
            else
                $identity = null;
        }

        if ($identity)
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $identity);
        else
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_UNCATEGORIZED, $identity);
    }
}
