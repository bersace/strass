<?php

class Wtk_Table_CellRenderer_Text extends Wtk_Table_CellRenderer
{
	protected $properties = array ('text' => '',
				       'wiki' => '');

	function element($data)
	{
		if ($data['wiki'])
			return new Wtk_Text($data['wiki']);
		else
			return new Wtk_RawText ($data['text']);
	}
}