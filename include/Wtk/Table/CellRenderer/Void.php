<?php

class Wtk_Table_CellRenderer_Void extends Wtk_Table_CellRenderer
{
	protected $properties = array ();

	function element($data)
	{
		return new Wtk_RawText('');
	}
}