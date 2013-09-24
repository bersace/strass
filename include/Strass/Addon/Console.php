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
		parent::initView($view);

		$view->auth_login_model = $this->login;
		$view->auth_logout_model = $this->logout;
		$view->individu = Zend_Registry::get('individu');

		$actions = array();
		$acl = Zend_Registry::get('acl');
		$user = Zend_Registry::get('user');
		$actual = Zend_Registry::get('actual_user');

		if ($acl->isAllowed($view->individu, 'membres')) {
			$t = new Inscriptions();
			$is = $t->fetchAll();
			if ($is->count()) {
				$actions[] = array('url'	=> $view->url(array('controller' => 'membres',
										    'action'	 => 'inscriptions')),
						   'label'	=> 'Nouvelles inscriptions');
			}
			$actions[] = array('url'	=> $view->url(array('controller' => 'membres',
									    'action'	 => 'lister')),
					   'label'	=> 'Membres');
			$actions[] = array('url'	=> $view->url(array('controller' => 'log'), true),
					   'label' 	=> 'Journaux système');
		}

		if ($view->individu) {
			$us = $view->individu->getUnites();
			if (count($us) == 1) {
				$u = current($us);
				$actions[] = array('url' => $view->url(array('controller' => 'unites',
									     'action' => 'accueil',
									     'unite' => $u->id),
								       true),
						   'label' => wtk_ucfirst($u->getFullName()));
				$actions[] = array('url' => $view->url(array('controller' => 'activites',
									     'action'	=> 'index'),
								       true),
						   'label' => 'Votre calendrier');
			}
			$actions[] = array('url'	=> $view->url(array('controller' => 'individus',
									    'action'	 => 'voir',
									    'individu'	=> $view->individu->id),
								      null, true),
					   'label'	=> 'Votre fiche');

		}

		$actions[] = array('url'	=> $view->url(array('controller' => 'membres',
								    'action'	 => 'profil'),
							      null, true),
				   'label'	=> 'Éditer votre compte');
    

		if ($actual && $user->username != $actual->username) {
			$actions[] = array('url'	=> $view->url(array('controller' => 'membres',
									    'action'	=> 'unsudo'),
								      null, true),
					   'label'	=> 'Redevenir '.$actual->username);
									    
		}

		$view->actions = $actions;
	}
	public function viewScript()
	{
		return 'console';
	}
}
