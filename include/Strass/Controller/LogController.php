<?php
require_once 'Strass/Log.php';

class LogController extends Strass_Controller_Action
{
	function indexAction()
	{
		$tl = new Logs;
		$this->assert(null, $tl, 'lire',
			      "Vous n'avez pas le droit d'accédez aux journaux système.");

		$s = $tl->getAdapter()->select()->from('log')->order('date DESC');
		$p = $this->_helper->Page();
		$this->view->logs = new Strass_Pages_Model_Select($s, 30, $p);
	}
}