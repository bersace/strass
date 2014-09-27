<?php

class Wtk_Document extends Wtk_Section
{
	public		$metas;
	protected	$custom_style_components;
	protected	$alternatives = array();
	protected	$template;

	function __construct ($metas = null)
	{
		parent::__construct ('document', null);
		$this->setMetas($metas);
		$this->sitemap = null;
		$this->styles = array();
		$this->custom_style_components = array();
		$this->embedStyle(false);
		$this->header = new Wtk_Section('header', null);
		$this->aside = new Wtk_Section('aside', null);
		$this->footer = new Wtk_Section('footer', null);
	}

	function setMetas($metas)
	{
	  if (!$metas)
	    $metas = new Wtk_Metas;

		$this->metas = $metas;
		$this->setTitle($metas->get('DC.Title'));
		if (!$metas->has('DC.Title.alternative'))
			$metas->set('DC.Title.alternative',
				    $metas->get('DC.Title'));
	}

	function getFooter()
	{
		return $this->footer;
	}

	function embedStyle($embed = true)
	{
		$this->embed_style = $embed;
	}

	function setStyle(Wtk_Document_Style $style)
	{
		$this->default_style = $style;

		if (!in_array($style, $this->styles))
			array_push($this->data['styles'], $style);
	}

	function getStyle()
	{
		return $this->default_style;
	}

	function setStyles($styles)
	{
		$styles = func_get_args();
		foreach ($styles as $style) {
			if (is_array($style))
				$this->styles = array_merge($this->styles, $style);
			else
				array_push ($this->data['styles'], $style);
		}
	}
	function addStyleComponents($comp)
	{
		$comps = func_get_args($comp);
		$this->custom_style_components = array_merge ($this->custom_style_components,
							      $comps);
	}

	function getStyleComponents()
	{
		return array_unique(array_merge($this->custom_style_components,
						parent::getStyleComponents()));
	}

	function addAlternative($href, $title, $type)
	{
		array_push($this->alternatives,
			   array('href' => $href,
				 'title' => $title,
				 'type' => $type));
	}

	function template()
	{
		$this->finalize();
		if (!$this->template) {
			if (!count($this->styles))
				$this->styles = array(new Wtk_Document_Style());

			if (!$this->default_style)
				$this->default_style = $this->styles[0];

			$this->style_components = $this->getStyleComponents();
			// todo: get form models.
			$djts = array_unique(array_filter($this->getDojoType()));

			$this->data['dojoTypes'] = $djts;
			$this->data['alternatives'] = $this->alternatives;
			$this->data['metas'] = $this->metas;
			$tpl = $this->elementTemplate();
			$tpl->addChild('header', $this->header->template());
			$tpl->addChild('content', $this->sectionTemplate());
			if (count($this->aside))
			  $tpl->addChild('aside', $this->aside->template());
			if (count($this->footer))
			  $tpl->addChild('footer', $this->footer->template());
			$this->template = $tpl;
		}

		return $this->template;
	}
}
