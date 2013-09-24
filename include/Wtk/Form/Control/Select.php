<?php

class Wtk_Form_Control_Select extends Wtk_Form_Control
{
  /**
   * @compact = NULL => auto
   */
  function __construct ($instance, $compact = NULL)
  {
    parent::__construct ($instance);

    if (!$instance instanceof Wtk_Form_Model_Instance_Enum) {
      throw new Exception (__CLASS__.' needs an Enum instance');
    }

    $this->setItems ($this->instance->getItems ());
    $this->setCompact ($compact);
    $this->multiple = $instance->getMultiple();
    if ($this->multiple) 
	    $this->addFlags('multiple');
    $this->select ($this->instance->get ());
  }

  protected function setItems ($values)
  {
    $this->data['items'] = $values;
  }

  function setCompact ($compact = TRUE)
  {
    $this->data['compact'] = $compact;
  }

  protected function select ($value)
  {
    $this->data['selected'] = $value;
  }
}

?>