<?php

class Wtk_Render_ODF_Context {
	protected $_parents;
	public	$doc;
	public	$content;

	function __construct($document)
	{
		$this->doc = $document;
		$this->content = $document->content;
		$this->_parents = array($this->content);
	}

	function pushParent($parent)
	{
		return $this->_parents[] = $parent;
	}

	function popParent()
	{
		return array_pop($this->_parents);
	}

	function __get($name)
	{
		if ($name == 'parent') {
			return end($this->_parents);
		}
	}
  }