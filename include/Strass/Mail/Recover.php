<?php

class Strass_Mail_Recover extends Strass_Mail
{
  function __construct($user) {
    parent::__construct("Recouvrement de compte");

    $this->user = $user;
  }

  function render()
  {
    $fc = Zend_Controller_Front::getInstance();
    $router = $fc->getRouter();
    $request = $fc->getRequest();
    $url = $router->assemble(array('controller' => 'membres',
				   'action' => 'recouvrir',
				   'confirmer' => $this->user->recover_token));
    $url = "http://".$request->getServer('HTTP_HOST').$url;
    $individu = $this->user->findParentIndividus();
    $config = Zend_Registry::get('config');

    $this->_doc->addText(<<<EOS

Bonjour {$individu->getFullName(false)},

Vous avez demandé à récupérer l'accès à votre compte sur {$config->site->short_title}.

**Si vous n'avez pas fait cette demande, ignorez ce message ou contactez l'administrateur du site !**

Pour récupérer l'accès à votre compte, réinitialisez votre mot de passe en suivant ce lien :

= [$url $url]

À bientôt sur {$config->site->short_title} !

FSS,
L'automate du site {$config->site->short_title}.
EOS
			 );
  }
}
