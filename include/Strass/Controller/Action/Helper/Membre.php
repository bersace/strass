<?php

require_once 'Strass/Individus.php';

class Strass_Controller_Action_Helper_Membre extends Zend_Controller_Action_Helper_Abstract
{
  function param()
  {
    $args = func_get_args();
    return call_user_func_array(array($this, 'direct'), $args);
  }

  function direct($default = null, $throw = true, $reset = true)
  {
    $username = $this->getRequest()->getParam('membre');
    $t = new Users;

    try {
      $user = $t->findByUsername($username);
    }
    catch (Zend_Db_Table_Exception $e) {
      $user = $default;
    }

    if (!$user) {
      if ($throw) {
	if ($username)
	  throw new Strass_Controller_Action_Exception_NotFound
	    ("Aucun membre ne correspond à //".$username."//.");
	else
	  throw new Strass_Controller_Action_Exception_Notice("Aucun membre spécifié.");
      }
      else
	return null;
    }

    if ($user->isMember()) {
      $individu = $user->findParentIndividus();
      $this->_actionController->branche->append($individu->getFullname(),
						array('controller' => 'individus',
						      'action'	 => 'fiche',
						      'individu'   => $individu->slug),
						array(),
						true);
    }

    return $user;
  }
}
