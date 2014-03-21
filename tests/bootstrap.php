<?php

$_ENV['STRASS_ROOT'] = 'tests/root/';
$_ENV['STRASS_UNIT_TEST']  = '1';

umask(0022);
date_default_timezone_set('Europe/Paris');
setlocale(LC_TIME, 'fr', 'fr_FR', 'fr_FR@euro', 'fr_FR.utf8', 'fr-FR', 'fra');

$paths = explode(':', get_include_path());
array_shift($paths);
array_unshift($paths, '.', dirname(__FILE__) . '/../include');
set_include_path(implode(':',$paths));

require_once('Orror.php');
require_once('Zend/Loader/Autoloader.php');
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('Dio_');
$loader->registerNamespace('Wtk_');
$loader->registerNamespace('Strass_');

Orror::init(E_ALL | E_STRICT);
mkdir($_ENV['STRASS_ROOT'].'/private/cache/', 0700, true);
Strass_Db::setup();
Zend_Registry::set('config', new Zend_Config(array(), true));
