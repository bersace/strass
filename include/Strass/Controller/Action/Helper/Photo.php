<?php

require_once 'Strass/Photos.php';

class Strass_Controller_Action_Helper_Photo extends Strass_Controller_Action_Helper_Album
{
  function direct($throw = true)
  {
    $slug = $this->getRequest()->getParam('photo');
    $t = new Photos;
    try {
      $p = $t->findBySlug($slug);
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw)
	throw new Zend_Controller_Exception("Photo inconnue");
      else
	return null;
    }

    $this->setBranche($p);

    return $p;
  }

  function setBranche($p) {
    $a = $p->findParentActivites();

    parent::setBranche($a);

    $this->_actionController->branche->append(wtk_ucfirst($p->titre),
					      array('controller'=> 'photos',
						    'action'	=> 'voir',
						    'photo'	=> $p->slug),
					      array(),
					      true);
  }
}
