<?php

class Wtk_Button extends Wtk_Container
{
  public	$label;
  
  function __construct ($label = NULL)
  {
    parent::__construct ();
    if ($label) {
      $text = new WtkText ($label);
      $this->addChild ($text);
    }
    $this->template	= 'button';
  }
}

?>