<?php

class Wtk_Form_Control_ColorChooser extends Wtk_Form_Control
{
  // todo gérer le javascript
  function __construct(Wtk_Form_Model_Instance $instance)
  {
    parent::__construct($instance);
    $this->value = $instance->getHex();
  }
}
