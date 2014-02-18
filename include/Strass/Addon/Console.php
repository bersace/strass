<?php

class Strass_Addon_Console extends Strass_Addon_Liens
{
  protected $login;
  protected $logout;

  function __construct()
  {
    parent::__construct('console', 'Console');

    $this->login = Zend_Registry::get('login_model');
    $this->logout = Zend_Registry::get('logout_model');
  }

  function initView($view)
  {
    $view->auth_login_model = $this->login;
    $view->auth_logout_model = $this->logout;
    $view->individu = Zend_Registry::get('individu');

    $acl = Zend_Registry::get('acl');
    $view->user = $user = Zend_Registry::get('user');
    $actual = Zend_Registry::get('actual_user');

    if ($view->individu) {
      $this->append('Votre fiche',
		    array('controller' => 'individus',
			  'action' => 'fiche',
			  'individu' => $view->individu->slug));
    }


    if ($actual && $user->username != $actual->username) {
      $this->append('Redevenir '.$actual->username,
		    array('controller' => 'membres',
			  'action'	=> 'unsudo'));
    }

    parent::initView($view);
  }

  public function viewScript()
  {
    return 'console';
  }
}
