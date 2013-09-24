<?php

class Knema_Format_PDF extends Knema_Format_Wtk
{
	protected	$_suffix = 'pdf';
	protected	$_mimeType = 'application/pdf';
	protected	$_title = 'PDF';
	protected	$_wtkRender = 'Pdf';
	protected	$_renderAddons = false;

	function getFilename($view)
	{
		return $view->document->metas->get('DC.Title').'.pdf';
	}
}