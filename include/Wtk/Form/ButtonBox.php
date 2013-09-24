<?php

class Wtk_Form_ButtonBox extends Wtk_Container
{

  function __construct()
  {
    parent::__construct();
    $buttons = func_get_args();
    call_user_func_array(array($this, "addChildren"), $buttons);
  }

  function template ()
  {
    $tpl = $this->elementTemplate();
    $tpl->addChild('buttons', $this->containerTemplate());
    return $tpl;
  }
}