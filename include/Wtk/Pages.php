<?php

class Wtk_Pages extends Wtk_Container
{
	protected	$model;
	protected	$renderer;

	function __construct($id,
			     Wtk_Pages_Model $model,
			     Wtk_Pages_Renderer $renderer)
	{
		parent::__construct();
		$this->id		= $id;
		$this->renderer	= $renderer;
		$this->model	= $model;
		$this->stylecomponent ='pages';
	}

	protected function _finalize()
	{
	  $cont = $this->addChild($this->renderer->renderContainer($this->model));
	  if ($this->model->count()) {
	    foreach($this->model as $id => $data) {
	      $child = $this->renderer->render($id, $data, $cont);
	    }

	    $this->renderer->renderLinks($this, $this->model);
	  }
	  else {
	    $this->renderer->renderEmpty($cont);
	  }

	  $this->_finalizeChildren();
	}

	function template()
	{
		$tpl = $this->elementTemplate();
		$tpl->addChild('page', $this->containerTemplate());
		return $tpl;
	}
}
