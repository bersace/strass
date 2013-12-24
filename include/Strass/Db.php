<?php

class Strass_Db extends Zend_Db {
  static function factory() {
    return Zend_Db::factory('Pdo_SQLite',
			    array ('dbname' => 'private/strass.sqlite'));
  }

  static function setup() {
    $db = Strass_Db::factory();
    Zend_Db_Table_Abstract::setDefaultAdapter($db);
    Zend_Registry::set('db', $db);
    $cache = Zend_Cache::factory('Core', 'File',
				 array('automatic_serialization' => true),
				 array('cache_dir' => 'private/cache'));
    Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
    return $db;
  }
}