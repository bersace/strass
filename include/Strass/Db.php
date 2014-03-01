<?php

class Strass_Db extends Zend_Db {
  static function setup($dbname = 'private/strass.sqlite')
  {
    if (!file_exists('private/cache'))
      mkdir('private/cache', 0700, true);


    $db = Zend_Db::factory('Pdo_SQLite', array ('dbname' => $dbname));
    Zend_Db_Table_Abstract::setDefaultAdapter($db);
    Zend_Registry::set('db', $db);
    try {
      $cache = Zend_Registry::get('cache_manager');
    }
    catch (Exception $e) {
      $cache = Zend_Cache::factory('Core', 'File',
				   array('automatic_serialization' => true),
				   array('cache_dir' => 'private/cache'));
    }
    Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

    return $db;
  }
}
