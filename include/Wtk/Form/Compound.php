<?php

class Wtk_Form_Compound extends Wtk_Container
{
	protected $caption;

	function __construct ($label)
	{
		parent::__construct();

		if ($label)
			$this->caption	= new Wtk_Inline ($label);
		else
			$this->caption  = null;

		$this->addFlags('control');
	}

	function __call($method, $args)
	{
		$cb = array($this->getParent('Wtk_Form'), $method);
		$wid = call_user_func_array($cb, $args);
		$wid->unparent();
		if ($wid instanceof Wtk_Form_Control) {
			$wid->useLabel(false);
		}
		$this->addChild($wid);
		return $wid;
	}

	function template()
	{
		$tpl = $this->elementTemplate('Wtk_Form_Control');
		if ($this->caption) {
			$caption = $this->caption->template();
			$tpl->addChild('caption', $caption);
		}
		$control = $this->containerTemplate();
		$tpl->addChild('control', $control);
		return $tpl;
	}
}
