<?php

class Wtk_Table_CellRenderer_Control extends Wtk_Table_CellRenderer
{
	protected $properties = array ('instance' => '');

	protected $args = array();
	protected $class;

	function __construct($class, array $args, $ikey)
	{
		parent::__construct('instance', $ikey);

		$this->class = $class;
		$this->args = $args;
	}

	function element($data)
	{
		$class = 'Wtk_Form_Control_'.$this->class;

		$args = $this->args;
		$i = $data['instance'];
		$i->label = NULL;
		array_unshift($args, $i);

		return wtk_new($class, $args);
	}
}