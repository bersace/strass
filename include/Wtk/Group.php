<?php

class Wtk_Group extends Wtk_Container
{
  function __construct ($label = NULL)
  {
    parent::__construct ();
    $this->data['label'] = $label;
  }
}

?>