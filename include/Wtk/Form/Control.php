<?php

abstract class Wtk_Form_Control extends Wtk_Element
{
	public		$instance;
	protected	$caption;

	function __construct (Wtk_Form_Model_Instance $instance)
	{
		parent::__construct ();
		$this->instance	= $instance;

		if ($instance->valid === FALSE)
			$this->addFlags('invalid');
		else if ($instance->valid === TRUE)
			$this->addFlags('valid');

		$this->useLabel();
		$this->setReadonly(false);
		$this->name	= $this->getName();
		$this->value	= $this->instance->get();
		$this->wtkConstraint = array();

		$this->setId(wtk_strtoid($this->instance->path));
	}

	function addConstraint($name)
	{
		$cs = $this->wtkConstraint;
		$cs[] = $name;
		$cs = array_unique($cs);
		$this->wtkConstraint = $cs;
	}

	protected function getName ()
	{
		$names = explode ('/', $this->instance->path);
		$name = array_shift ($names);
		$name.= '['.implode ('][', $names).']';
		return $name;
	}

	function useLabel($use = true)
	{
		if ($this->instance->label && $use)
			$this->caption = new Wtk_Inline ($this->instance->label);
		else
			$this->caption = null;

	}

	function setReadonly($readonly = true)
	{
		$this->readonly = (bool)$readonly;
	}

	function template ()
	{
		if (!$this->caption)
			$this->addFlags('nocaption');

		$tpl = $this->elementTemplate(__CLASS__);
		if ($this->caption) {
			$caption = $this->caption->template();
			$tpl->addChild ('caption', $caption);
		}
		$control = $this->elementTemplate();
		$tpl->addChild('control', $control);
		return $tpl;
	}
}

