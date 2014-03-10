<?php

class Wtk_Table_CellRenderer_Control extends Wtk_Table_CellRenderer
{
	public $properties = array ('instance' => '');

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
		$args = $this->args;
		$i = $data['instance'];

		$class = 'Wtk_Form_Control_'.$this->class;
		if (@class_exists($class)) {
		  $i->label = NULL;
		  array_unshift($args, $i);
		}
		else {
		  array_unshift($args, $i->value);
		  $class = 'Wtk_'.$this->class;
		}

		if (!@class_exists($class))
		  $class = $this->class;
		if (!@class_exists($class))
		  throw new Exception("Impossible de trouver le widget ".$this->class);

		return wtk_new($class, $args);
	}
}