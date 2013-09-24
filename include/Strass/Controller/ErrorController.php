<?php
require_once 'Zend/Controller/Action.php';

class Strass_ErrorController extends Strass_Controller_Action implements Knema_Controller_ErrorController
{
	protected $_titreBranche = 'Erreur';

	public function initAcl($acl)
	{
		// Permettre à tout le monde d'exécuter les actions de ce
		// controlleur.
		$acl->allow(null, $this);
	}

	public function init()
	{
		$this->_request->setParam('format', 'xhtml');
		parent::init();
	}

	public function errorAction()
	{
		$this->view->errors = $this->getResponse()->getException();
		// erreur HTTP ?
	}
}

