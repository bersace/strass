<?php
interface Strass_Controller_ErrorController {}

class ErrorController extends Strass_Controller_Action implements Strass_Controller_ErrorController
{
	protected $_titreBranche = 'Erreur';

	public function init()
	{
		$this->_request->setParam('format', 'xhtml');
		parent::init();
	}

	public function errorAction()
	{
		$this->view->errors = $this->getResponse()->getException();
		foreach($this->view->errors as $error) {
			if ($error instanceof Zend_Controller_Dispatcher_Exception) {
				$this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
				break;
			}
		}
		// erreur HTTP ?
		// http://framework.zend.com/manual/fr/zend.controller.plugins.html ErrorHandler
	}
}
