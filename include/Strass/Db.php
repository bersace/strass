<?php

class Strass_Db extends Zend_Db {
  static function setup($dbname = null, $reset=false)
  {
    if ($dbname === null)
      $dbname = Strass::getRoot().'data/private/strass.sqlite';

    if ($reset)
      @unlink($dbname);

    $db = Zend_Db::factory('Pdo_SQLite', array ('dbname' => $dbname));
    $doProfile = strpos(@$_SERVER['QUERY_STRING'], 'PROFILE') !== false || isset($_ENV['STRASS_UNIT_TEST']);
    $db->getProfiler()->setEnabled($doProfile);

    Zend_Db_Table_Abstract::setDefaultAdapter($db);
    Zend_Registry::set('db', $db);
    $cache = Zend_Registry::get('cache');
    Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

    Strass_Db_Table_Abstract::$_rowCache = new Strass_Cache;

    return $db;
  }
}
