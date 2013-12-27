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
    $this->_doc->addLink($url);
  }
}