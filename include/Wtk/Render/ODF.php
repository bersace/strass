<?php

abstract class Wtk_Render_ODF extends Wtk_Render
{
	protected $doc;

	function __construct($document)
	{
		parent::__construct($document);
		if (exec("zip -?"))
			$this->doc = new Dio_Archive($this->mime);
		else
			$this->doc = new Dio_Flat($this->mime);

		// metas
		$m0 = $document->metas;
		$m = $this->doc->metas;
		$m->setCreator($m0->get('DC.Creator'));
		$m->setTitle($m0->get('DC.Title'));
		$m->addKeywords(explode(',', $m0->get('DC.Subject')));
	}

	protected function style($file, $document)
	{
		if (is_readable($file))
			include $file;
	}

	function output()
	{
		$comps = $this->document->getStyleComponents();
		$style = $this->document->getStyle();

		foreach($comps as $comp) {
			$file = 'data/styles/'.$style->id.'/'.$this->template.'/'.$comp.'.php';
			$this->style($file, $this->doc);
		}

		$template = $this->document->template();
		$context = new Wtk_Render_ODF_Context($this->doc);
		$template->addData(array('_context' => $context));
		$template->output($this->template);

		echo $this->doc->render();
	}
}
