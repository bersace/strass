<?php

class Wtk_Pages_Model_Assoc extends Wtk_Pages_Model_Unique
{
	protected	$pages_id;
	protected	$pointer;

	function __construct(array $data, $current = null)
	{
		parent::__construct($data, 1, $current);
		$this->pages_id	= array_keys($data);
		$this->current	= in_array($current, $this->pages_id) ? $current : reset($this->pages_id);
	}

	function getPagesIds()
	{
		return $this->pages_id;
	}

	function pagesCount()
	{
		return count($this->data);
	}

	function getCurrentPageId()
	{
		return $this->current;
	}

	function getPrevId($ref = null)
	{
		return $this->getRelId($ref, -1);
	}

	function getNextId($ref = null)
	{
		return $this->getRelId($ref, +1);
	}

	protected function getRelId($ref, $sens)
	{
		$ref = $ref ? $ref : $this->current;
		$r = array_flip($this->pages_id);
		$i = $r[$ref]+$sens;
		return array_key_exists($i, $this->pages_id) ? $this->pages_id[$i] : null;
	}

	function fetch($id = null)
	{
		$id = $id ? $id : $this->getCurrentPageId();
		return $this->data[$id];
	}

	public function valid()
	{
		return array_key_exists($this->pointer, $this->data);
	}
}

