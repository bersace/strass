<?php

class Wtk_Table_CellRenderer_Void extends Wtk_Table_CellRenderer
{
	function element($data)
	{
		return new Wtk_RawText('');
	}
}