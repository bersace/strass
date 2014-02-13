<?php

$config = new Strass_Config_Php('strass',
			      array('site' => array('style' => 'strass',
						    'metas' => array('title' => 'Strass'))));
Zend_Registry::set('config', $config);

try {
  $fc = Zend_Controller_Front::getInstance();

  $fc->setRequest(new Strass_Controller_Request_Http);
  $fc->setParam('noViewRenderer', true);
  $fc->setModuleControllerDirectoryName('Installer');
  $fc->addControllerDirectory('include/Strass/Installer/Controller', 'Strass');
  $fc->setDefaultModule('Strass');
  $fc->registerPlugin(new Strass_Controller_Plugin_Error);

  $fc->dispatch();

  Zend_Session::writeClose();
}
catch (Exception $e) {
  // affichage complet des exceptions non intercepté par le controlleur. À améliorer.
  $msg = ":(\n\n";
  $msg.= $e->getMessage()."\n\n";
  $msg.= " à ".$e->getFile().":".$e->getLine()."\n\n";
  $msg.= str_replace ('#', '<br/>#', $e->getTraceAsString())."\n";
  Orror::kill(strip_tags($msg));
}
