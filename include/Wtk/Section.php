<?php

class Wtk_Section extends Wtk_Container
{
	public		$level;
	protected	$content;
	protected	$title;

	/**
	 * @title is either a wiki string or a element.
	 */
	function __construct ($id = null, $title = NULL)
	{
		parent::__construct ();
		$this->id	= $id;
		$this->level = $title ? 1 : 0;
		$this->setTitle($title);
		$this->contents	= array ();
	}

	function setTitle($title)
	{
		$o = $this->title;
		if ($title instanceof Wtk_Element)
			$this->title	= $title;
		else if (!$title)
			$this->title = null;
		else if (is_string($title))
			$this->title	= new Wtk_RawText($title);

		if ((!$o && $this->title) || ($o && !$this->title))
			$this->_computeLevel(true);
	}

	function _computeLevel($children = false)
	{
		$parent = $this->getParent('Wtk_Section');
		if ($parent)
			$this->level = intval($parent->level) + ($this->title ? 1 : 0);
		else
			$this->level = 1;

		if ($children) {
			foreach ($this->children as $child) {
				if ($child instanceof Wtk_Section) {
					$child->_computeLevel($children);
				}
			}
		}
	}

	function parent($wid)
	{
		parent::parent($wid);
		$this->_computeLevel();
	}

	function template ()
	{
		return $this->sectionTemplate ();
	}

	protected function sectionTemplate ()
	{
		$this->data['level']	= $this->level;
		$this->data['stringTitle'] = (string) $this->title;
		$tpl = $this->elementTemplate(__CLASS__);
		if ($this->title)
			$tpl->addChild('title', $this->title->template());

		$this->addChildrenTemplate($tpl);
		return $tpl;
	}
}