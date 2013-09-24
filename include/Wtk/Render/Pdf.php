<?php

class Wtk_Render_Pdf extends Wtk_Render
{
	protected	$template = 'pdf';
	protected	$mime	= 'application/pdf';

	function output()
	{
		$template = $this->document->template();
		$font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD, 10);
		$ctx = new PL_Context($font);
		$template->addData(array('ctx' => $ctx));
		$template->output($this->template);
		echo $ctx->pdf->render();
	}
}