<?php
interface Strass_Controller_ErrorController {}

class ErrorController extends Strass_Controller_Action implements Strass_Controller_ErrorController
{
  public function errorAction()
  {
    $this->view->errors = $errors = $this->getResponse()->getException();

    /* Journal système */
    foreach ($errors as $error) {
      if ($error instanceof Zend_Controller_Dispatcher_Exception)
      	continue;
      if ($error instanceof Zend_Controller_Action_Exception)
      	if ($error->getCode() <= 500)
      	  continue;

      ob_start();
      var_dump($error);
      $details = ob_get_contents();
      ob_end_clean();

      try {
	$this->logger->error($error->getMessage(), null, $details);
      }
      catch (Exception $e) {
	error_log($error->getMessage());
	error_log($e->getMessage());
      }
    }

    /* Code HTTP */
    foreach($errors as $error) {
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

    $this->_request->setParam('format', 'xhtml');
  }
}
