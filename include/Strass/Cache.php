<?php

class Strass_Cache
{

  static function setup()
  {
    $cachedir = Strass_Version::getRoot().'private/cache';
    if (!file_exists($cachedir))
      mkdir($cachedir, 0700, true);
    Zend_Registry::set('cache',
		       Zend_Cache::factory('Core', 'Strass_Cache_Backend_File',
					   array('automatic_serialization' => true),
					   array('cache_dir' => $cachedir),
					   false, /* custom frontend */
					   true /* custom backend */
					   ));
  }

  function __construct()
  {
    $this->data = array();
  }


  function has($id)
  {
    return array_key_exists($id, $this->data);
  }


  function load($id)
  {
    if (!$this->has($id))
      return false;
    return $this->data[$id];
  }

  function save($value, $id)
  {
    $this->data[$id] = $value;
  }
}
