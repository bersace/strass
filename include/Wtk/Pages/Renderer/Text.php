<?php

class Wtk_Pages_Renderer_Text extends Wtk_Pages_Renderer
{
	protected $imgdir;

	function __construct($href, $imgdir, $intermediate = true, $labels = array())
	{
		parent::__construct($href, $intermediate, $labels);
		$this->imgdir = $imgdir;
	}

	function render($id, $data, $container)
	{
		$t = $container->addText($data);
		$tw = $t->getTextWiki();
		$tw->setRenderConf('Xhtml', 'image', 'base', $this->imgdir);
	}

	function getLabel($id)
	{
		return strval($this->model->titles[$id]);
	}
}