<?php

class Strass_Controller_Action_Helper_Livredor extends Zend_Controller_Action_Helper_Abstract
{
  function param()
  {
    $args = func_get_args();
    return call_user_func_array(array($this, 'direct'), $args);
  }

  function direct($throw = true)
  {
    $id = $this->getRequest()->getParam('message');
    if (!$id)
	throw new Strass_Controller_Action_Exception_Notice("Message non spécifié.");

    $t = new Livredor;
    $row = $t->find($id)->current();

    if (!$row)
      if ($throw)
	throw new Strass_Controller_Action_Exception_NotFound("Message ".$id." inexistant.");

    return $row;
  }
}
