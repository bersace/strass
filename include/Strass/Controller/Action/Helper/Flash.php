<?php

class Strass_Controller_Action_Helper_Flash extends Zend_Controller_Action_Helper_Abstract
{
  function direct($level, $message, $detail=null)
  {
    $flash = new Strass_Flash($level, $message, $detail);
    $flash->save();
    return $flash;
  }

  function info($message, $detail=null)
  {
    return $this->direct(Strass_Flash::LEVEL_INFO, $message, $detail);
  }

  function warn($message, $detail=null)
  {
    return $this->direct(Strass_Flash::LEVEL_WARNING, $message, $detail);
  }
}
