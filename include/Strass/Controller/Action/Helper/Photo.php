<?php

require_once 'Strass/Photos.php';

class Strass_Controller_Action_Helper_Photo extends Strass_Controller_Action_Helper_Activite
{
  function direct($throw = true)
  {
    $a = parent::direct(null, $throw, true,
			array('controller'  => 'photos',
			      'action'	=> 'consulter'));

    $slug = $this->getRequest()->getParam('photo');
    $tp = new Photos();
    try {
      $p = $tp->findBySlugs($a->slug, $slug);
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw)
	throw new Zend_Controller_Exception("Photo inconnue");
    }

    if ($p)
      $this->_actionController->branche->append(wtk_ucfirst($p->titre),
						array('controller'	=> 'photos',
						      'action'	=> 'voir',
						      'activite'	=> $a->slug,
						      'photo'		=> $p->slug),
						array(),
						true);

    return array($a, $p);
  }
}
