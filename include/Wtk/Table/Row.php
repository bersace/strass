<?php

class Wtk_Table_Row extends Wtk_Container
{
	function addColumn()
	{
		$columns = func_get_args ();
		foreach ($columns as $column) {
			if ($column instanceof Wtk_Element)
				$this->addChild($column);
			else
				$this->addText($column);
		}
	}

	function template()
	{
		return $this->containerTemplate(__CLASS__);
	}
}
