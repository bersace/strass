<?php

class Strass_Addon_Console extends Strass_Addon_Liens
{
    function __construct()
    {
        parent::__construct('console', 'Console');
    }

    function initView($view)
    {
        $view->auth_login_model = Zend_Registry::get('login_model');
        $view->auth_logout_model = Zend_Registry::get('logout_model');

        $acl = Zend_Registry::get('acl');
        $user = Zend_Registry::get('user');

        try {
            $sudoer = Zend_Registry::get('sudoer');
            $this->append('Redevenir '.$sudoer->username,
            array('controller' => 'membres', 'action' => 'unsudo'));
        }
        catch (Zend_Exception $e) {}

        parent::initView($view);
    }

    public function viewScript()
    {
        return 'console';
    }
}
