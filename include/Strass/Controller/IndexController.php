<?php

require_once 'Strass/Activites.php';
require_once 'Strass/Photos.php';

class IndexController extends Strass_Controller_Action implements Zend_Acl_Resource_Interface
{
	protected $_titreBranche = 'Accueil';
   
	function init()
	{
		parent::init();
		$acl = Zend_Registry::get('acl');
		$acl->add($this);
		$unites = new Unites();
		$racine = $unites->fetchAll('unites.parent IS NULL')->current();
		$acl->allow($racine->getRoleRoleId('chef'), $this);
	}

	public function getResourceId()
	{
		return (string) Zend_Registry::get('site')->id;
	}

	public function indexAction()
	{
	  $unite = $this->_helper->Unite();
	  if ($unite) {
	    $this->redirectSimple('accueil', 'unites', null, array('unite' => $unite->id));
	  } else {
	    Orror::kill("Pas d'unités");
	  }
	}

	function editerAction()
	{
		$this->assert(null, $this, 'editer',
			      "Vous n'avez pas le droit d'administrer ce site");

		$m = new Wtk_Form_Model('intro');
		$m->addString('introduction', "Introduction", file_get_contents('data/intro.wiki'));
		$m->addNewSubmission('enregistrer', 'Enregistrer');

		if ($m->validate()) {
			file_put_contents('data/intro.wiki', $m->get('introduction'));
			$this->redirectSimple('index');
		}

		$this->view->model = $m;
	}
}
