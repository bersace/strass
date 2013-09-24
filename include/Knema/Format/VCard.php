<?php
require_once 'vcard.php';

class Knema_Format_VCard extends Knema_Format
{
	protected	$_suffix	= 'vcf';
	protected	$_mimeType	= 'text/x-vcard';
	protected	$_title		= "Carnet d'adresse";
	protected	$_viewSuffix	= 'vcard';
	protected	$_renderFooter	= false;

	protected function _preRender($controller)
	{
		$controller->view->vcards = array();
	}

	protected function _render($view)
	{
		$output = "";
		foreach($view->vcards as $vcard)
			$output.= $vcard->getVCard()."\n\n";

		return $output;
	}

	function getFilename($view)
	{
		return str_replace(' ', '_', $view->page->metas->get('DC.Title.alternative')).'.'.$this->_suffix;
	}
}