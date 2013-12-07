<?php

umask(0022);
date_default_timezone_set('Europe/Paris'); // config/knema/site ?

$paths = explode(':', get_include_path());
array_shift($paths);
array_unshift($paths,'.', dirname(__FILE__).'/include');
set_include_path(implode(':',$paths));

require_once('Orror.php');
require_once('Zend/Loader/Autoloader.php');
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('Dio_');
$loader->registerNamespace('Wtk_');
$loader->registerNamespace('Knema_');
$loader->registerNamespace('Strass_');

mkdir('old', 0700);
mkdir('private/cache', 0700, true);

// config
Zend_Registry::set('config_basedir', 'config/');
$config = array();;
$tmp = new Knema_Config_Php('knema/db');
$newpath = 'private/strass.sqlite';
rename($tmp->config->dbname, $newpath);
$tmp->config->dbname = $newpath;
$config['db'] = $tmp->toArray();
$tmp = new Knema_Config_Php('knema/site');
$config['site'] = $tmp->toArray();
$tmp = new Knema_Config_Php('strass/inscription');
$config['inscription'] = $tmp->toArray();
$tmp = new Knema_Config_Php('knema/menu');
$config['menu'] = $tmp->toArray();

// plus d'index séparé
unlink('config/strass/index.php');
// remplacé par include/…
unlink('config/knema/formats.php');

Zend_Registry::set('config_basedir', 'private/config/');
$config = new Knema_Config_Php('strass', $config);
$config->write();


// Vidages de resources/
rename('resources/styles', 'data/styles');

// Renommages
rename('data/statiques', 'private/statiques');

// Archivage
rename('resources', 'old/resources');
rename('config', 'old/config');
rename('data/db', 'old/db');

echo "OK !\n";
