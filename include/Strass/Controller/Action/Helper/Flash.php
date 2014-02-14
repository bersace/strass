<?php

class Strass_Controller_Action_Helper_Flash extends Zend_Controller_Action_Helper_Abstract
{
  function direct($level, $message)
  {
    $flash = new Strass_Flash($level, $message);
    $flash->save();
    return $flash;
  }

  function info($message)
  {
    return $this->direct(Strass_Flash::LEVEL_INFO, $message);
  }

  function warn($message)
  {
    return $this->direct(Strass_Flash::LEVEL_WARNING, $message);
  }
}
