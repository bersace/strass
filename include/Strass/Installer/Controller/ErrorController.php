<?php

class ErrorController extends Strass_Installer_Controller_Action
{
  public function errorAction()
  {
    $this->view->errors = $this->getResponse()->getException();
    foreach($this->view->errors as $error) {
      if ($error instanceof Zend_Controller_Dispatcher_Exception) {
	$this->getResponse()->setHttpResponseCode(404);
	break;
      }
      if ($error instanceof Zend_Controller_Action_Exception) {
	try {
	  $this->getResponse()->setHttpResponseCode($error->getCode());
	} catch (Zend_Controller_Response_Exception $e) {}
	break;
      }
    }
  }
}
