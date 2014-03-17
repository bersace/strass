<?php

class Wtk_Form_Control_File extends Wtk_Form_Control
{
  protected $button;

  function __construct (Wtk_Form_Model_Instance $instance, $button = null)
  {
    parent::__construct($instance);
    if (!$button) {
      $button = new Wtk_Form_Button("Téléverser");
      $button->addFlags('label');
    }
    $this->button = $button;
  }

  function template()
  {
    $tpl = parent::template();
    $button = $this->button->template();
    $tpl->control->addChild('button', $button);
    return $tpl;
  }
}
