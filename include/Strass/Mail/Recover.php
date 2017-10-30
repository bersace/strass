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
    $host = $request->getServer('HTTP_HOST');
    $url = $router->assemble(array('controller' => 'membres',
				   'action' => 'recouvrir',
				   'confirmer' => $this->user->recover_token));
    $url = "http://".$host.$url;
    $individu = $this->user->findParentIndividus();
    $this->_doc->addText(<<<EOS

Bonjour {$individu->prenom},

Vous souhaitez récupérer l'accès à votre compte sur {$host}.

**Si vous n'avez pas fait cette demande, ignorez ce message ou contactez l'administrateur du site !**

Pour récupérer l'accès à votre compte, réinitialisez votre mot de passe en suivant ce lien :

= [$url $url]

À bientôt sur [http://$host {$host}] !

FSS,
L'automate.
EOS
			 );
  }
}
