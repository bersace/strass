<?php

class Wtk_Link extends Wtk_Container
{
	function __construct ($href, $metas = NULL, $child = NULL)
	{
		parent::__construct();

		$this->metas = new Wtk_Metas(array('title' => is_string($metas) ? $metas : $href,
						   'label' => is_string($metas) ? $metas : basename($href)));

		if ($metas instanceof Wtk_Metas)
			$this->metas->merge($metas);

		if ($child instanceof Wtk_Element)
			$this->addChild($child);
		else
			$this->addRawText($this->metas->label);

		$this->metas	= $this->metas;
		$this->href	= $href;
	}

	function __toString()
	{
		return $this->metas->label;
	}
}