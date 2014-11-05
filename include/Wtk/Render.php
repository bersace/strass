<?php

abstract class Wtk_Render
{
	public	$template;	// template suffix
	protected	$mime;		// type mime
	protected	$document;	// root Wtk_Element.

	static function factory ($document, $format)
	{
		$class = 'Wtk_Render_'.$format;
		return new $class ($document);
	}

	function __construct ($document)
	{
		$this->document = $document;
	}

	function render ()
	{
		ob_start();
		$this->output();
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	function output ()
	{
		$template = $this->document->template();
		$template->output($this->template);
	}

	function getMimeType()
	{
		return $this->mime;
	}

	function send()
	{
		if (!headers_sent()) {
			$mime = $this->getMimeType();
			header('Content-Type: '.$mime.'; charset=utf-8');
		}
		$this->output();
	}
}
