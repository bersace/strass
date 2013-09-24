<?php

class Wtk_List extends Wtk_Container
{
	function __construct ($ordered = FALSE)
	{
		parent::__construct ();
		$this->setOrdered ($ordered);
	}

	function setOrdered ($ordered = TRUE)
	{
		$this->data['ordered'] = $ordered;
	}

	function addItem($child0 = null)
	{
		$children = is_array($child0) ? $child0 : func_get_args();
		$item = $this->addList_Item($children, $this->ordered);
		return $item->addFlags(count($this->children) % 2 ? 'odd' : 'even');
	}

	function template ()
	{
		$tpl = $this->elementTemplate(__CLASS__);
		$this->addChildrenTemplate($tpl);

		if ($this->ordered)
			foreach($tpl as $no => $child)
				$child->addData(array('no' => $no));

		return $tpl;
	}
}
