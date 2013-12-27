<?php /*-*- php -*-*/
umask(0022);
date_default_timezone_set('Europe/Paris');

$paths = explode(':', get_include_path());
array_shift($paths);
array_unshift($paths,'.', dirname(__FILE__).'/include');
set_include_path(implode(':',$paths));

require_once('Zend/Loader/Autoloader.php');
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('Dio_');
$loader->registerNamespace('Wtk_');
$loader->registerNamespace('Strass_');

try {
  $fc = Zend_Controller_Front::getInstance();

  $request = new Strass_Controller_Request_Http();
  $fc->setRequest($request);

  $routeur = $fc->getRouter();
  $routeur->removeDefaultRoutes();

  $p = '([[:alpha:]]+)';
  $f = '(xhtml|ics|vcf|rss|atom|pdf|tex|txt|od[ts]|csv)';
  $vars = array('controller' => array($p, 'index'),
		'action'     => array($p, 'index'),
		'format'     => array($f, 'xhtml'));

  $pattern = '[%controller%[/%action%][.%format%]*]';
  $opattern = null;
  $route = new Strass_Controller_Router_Route_Uri($vars, $pattern, $opattern);
  $routeur->addRoute('default', $route);
  $fc->setParam('noViewRenderer', true);

  $fc->setModuleControllerDirectoryName('Controller');
  $fc->addControllerDirectory('include/Strass/Controller', 'Strass');
  Zend_Controller_Action_HelperBroker::addPrefix('Strass_Controller_Action_Helper');
  $fc->setDefaultModule('Strass');

  // greffons
  $fc->registerPlugin(new Strass_Controller_Plugin_Error);
  $fc->registerPlugin(new Strass_Controller_Plugin_Db);
  $fc->registerPlugin(new Strass_Controller_Plugin_Auth);
  $fc->registerPlugin(new Strass_Controller_Plugin_Page);

  $fc->dispatch();

  Zend_Session::writeClose();
}
catch (Exception $e) {
  // affichage complet des exceptions non intercepté par le controlleur. À améliorer.
  $msg = ":(<br/>\n";
  $msg.= $e->getMessage()."<br/>\n";
  $msg.= " à ".$e->getFile().":".$e->getLine()."<br/>\n";
  $msg.= str_replace ('#', '<br/>#', $e->getTraceAsString())."<br/>\n";
  Orror::kill(strip_tags($msg));
}


$conf = new Strass_Config_Php('strass');
if ($conf->site->sauvegarder) {

  // sauvegarde des modifications récente de la BD.
  clearstatcache();
  $db = 'private/strass.sqlite';
  $time = time() - filemtime($db);

  if ($time <= 2) {
    $username = Zend_Registry::get('user')->username;
    copy($db, $db.'~'.date('Y-m-d-H-i-s').'-'.$username);
  }
}