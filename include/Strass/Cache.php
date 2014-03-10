<?php

class Strass_Cache
{
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
