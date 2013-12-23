#!/usr/bin/php
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
$loader->registerNamespace('Strass_');

rename('cache', 'private/cache');

// config
Zend_Registry::set('config_basedir', 'config/');
$config = array();;
$tmp = new Strass_Config_Php('knema/db');
$newpath = 'private/strass.sqlite';
rename($tmp->config->dbname, $newpath);
$tmp->config->dbname = $newpath;
$config['db'] = $tmp->toArray();
$tmp = new Strass_Config_Php('knema/site');
$config['site'] = $tmp->toArray();
$tmp = new Strass_Config_Php('strass/inscription');
$config['inscription'] = $tmp->toArray();
$tmp = new Strass_Config_Php('knema/menu');
$config['menu'] = $tmp->toArray();

Zend_Registry::set('config_basedir', 'private/config/');
$config = new Strass_Config_Php('strass', $config);
$config->write();

// Renommages
rename("resources/styles/".$config->site->style, "data/styles/");
rename('data/statiques/', 'private/statiques');

// Nettoyages
shell_exec('rm -rf resources/ config/ data/db/');

echo "OK !\n";
