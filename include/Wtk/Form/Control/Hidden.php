<?php

class Wtk_Form_Control_Hidden extends Wtk_Form_Control
{
  function __construct ($instance)
  {
    parent::__construct ($instance);
    $this->caption = null;
  }

  function template ()
  {
    return $this->elementTemplate ();
  }
}

?>