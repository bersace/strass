<?php

class Strass_Controller_Action_Helper_Page extends Zend_Controller_Action_Helper_Abstract
{
	function direct($fallback = true)
	{
		$page = $this->getRequest()->getParam('page');
		$page = $page ? $page : ($fallback ? 1 : null);
		return intval($page);
	}
}
