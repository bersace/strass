<?php

class Strass_Flash_Empty extends Exception {}

class Strass_Flash
{
  const LEVEL_INFO = 'info';
  const LEVEL_WARNING = 'warn';

  function __construct($level, $message)
  {
    $this->level = $level;
    $this->message = $message;
  }

  function save()
  {
    $session = new Zend_Session_Namespace('strassFlash');
    $session->flash = $this;
    return $this;
  }

  function clear()
  {
    $session = new Zend_Session_Namespace('strassFlash');
    $session->flash = null;
    return $this;
  }

  static function current()
  {
    $session = new Zend_Session_Namespace('strassFlash');
    if ($session->flash)
      return $session->flash;
    else
      throw new Strass_Flash_Empty;
  }
}
