<?php

class Strass_Flash_Empty extends Exception {}

class Strass_Flash
{
  const LEVEL_INFO = 'info';
  const LEVEL_WARNING = 'warn';

  function __construct($level, $message, $detail)
  {
    $this->level = $level;
    $this->message = $message;
    $this->detail = $detail;
  }

  static function getSessionNamespace()
  {
    return new Zend_Session_Namespace('strassFlash');
  }


  function save()
  {
    self::getSessionNamespace()->flash = $this;
    return $this;
  }

  function clear()
  {
    self::getSessionNamespace()->flash = null;
    return $this;
  }

  static function current()
  {
    $session = self::getSessionNamespace();
    if ($session->flash)
      return $session->flash;
    else
      throw new Strass_Flash_Empty;
  }
}
