<?php

putenv('STRASS_UNIT_TEST=1');

$paths = explode(':', get_include_path());
array_shift($paths);
array_unshift($paths, '.', dirname(__FILE__) . '/../include');
set_include_path(implode(':',$paths));

require_once('Strass.php');
require_once('Orror.php');

chdir(realpath(getenv('STRASS_ROOT')));

Strass::bootstrap();
Orror::init(E_ALL | E_STRICT);

Strass_Cache::setup();
Strass_Db::setup();
Zend_Registry::set('config', new Zend_Config(array(), true));
