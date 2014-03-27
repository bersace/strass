<?php /*-*- php -*-*/

$paths = explode(':', get_include_path());
array_shift($paths);
array_unshift($paths,'.', dirname(__FILE__).'/include');
set_include_path(implode(':',$paths));

require_once 'Strass.php';

return Strass::main();
