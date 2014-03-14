<?php /*-*- php -*-*/
umask(0022);
date_default_timezone_set('Europe/Paris');
setlocale(LC_TIME, 'fr', 'fr_FR', 'fr_FR.utf8', 'fr_FR@euro', 'fr-FR', 'fra');

$paths = explode(':', get_include_path());
array_shift($paths);
array_unshift($paths,'.', dirname(__FILE__).'/include');
set_include_path(implode(':',$paths));

require_once 'Wtk.php';
require_once 'Zend/Loader.php';
@Zend_Loader::registerAutoload();

if (Strass_Version::onMaintenance()) {
  Strass_Version::showMaintenance();
}

if (!Strass_Version::isInstalled()) {
  Strass_Installer::main();
  return;
}

try {
  Zend_Registry::set('config', new Strass_Config_Php('strass'));
  if (!file_exists('private/cache'))
    mkdir('private/cache', 0700, true);
  Zend_Registry::set('cache',
		     Zend_Cache::factory('Core', 'File',
					 array('automatic_serialization' => true),
					 array('cache_dir' => 'private/cache')));

  $fc = Zend_Controller_Front::getInstance();

  $request = new Strass_Controller_Request_Http();
  $fc->setRequest($request);

  $routeur = $fc->getRouter();
  $routeur->removeDefaultRoutes();

  $p = '([[:alpha:]]+)';
  $f = '(xhtml|ics|vcf|rss|atom|pdf|tex|txt|od[ts]|csv)';
  $vars = array('controller' => array($p, 'unites'),
		'action'     => array($p, 'index'),
		'format'     => array($f, 'xhtml'),
		'annee'      => array('([[:digit:]]{4})', null));

  $pattern = '[%controller%[/%action%][.%format%][/%annee%]*]';
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

  $fc->dispatch();

  Zend_Session::writeClose();
}
catch (Exception $e) {
  // Close session, if started
  if (class_exists('Zend_Session', false) && Zend_Session::isStarted()) {
    Zend_Session::writeClose();
  } elseif (isset($_SESSION)) {
    session_write_close();
  }

  try {
    try {
      $logger = Zend_Registry::get('logger');
    }
    catch (Exception $_) {
      $logger = new Strass_Logger;
    }

    $logger->critical($e->getMessage(), null, print_r($e, true));
  }
  catch(Exception $_) {}


  // affichage complet des exceptions non intercepté par le controlleur. À améliorer.
  $msg = ":(\n\n";
  $msg.= $e->getMessage()."\n\n";
  $msg.= " à ".$e->getFile().":".$e->getLine()."\n\n";
  $msg.= str_replace ('#', '<br/>#', $e->getTraceAsString())."\n";
  error_log(strtok($e->getMessage(), "\n"));
  Orror::kill(strip_tags($msg));
}

if (@strpos($_SERVER['QUERY_STRING'], 'PROFILE') !== false) {
  $db = Zend_Registry::get('db');
  $profiler = $db->getProfiler();
  $fd = fopen('sql-profile.csv', 'w');
  foreach ($profiler->getQueryProfiles() as $query) {
    $sql = str_replace("\n", " ", $query->getQuery());
    $time = $query->getElapsedSecs();
    fputcsv($fd, array($time, $sql));
  }
  fclose($fd);
}
