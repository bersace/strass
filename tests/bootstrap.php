<?php

umask(0022);
date_default_timezone_set('Europe/Paris');

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
Strass_Db::setup(':memory:');
