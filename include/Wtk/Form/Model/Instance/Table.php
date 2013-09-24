<?php

class Wtk_Form_Model_Instance_Table extends Wtk_Form_Model_Instance_Group
{
	protected	$model;
	protected	$constraints;
	public		$reorderable;
	public		$extensible;

	function __construct($path, $label, $model, $reorderable = TRUE, $extensible = TRUE)
	{
		parent::__construct($path, $label, array());
		$this->model = $model;
		$this->reorderable = $reorderable;
		$this->extensible = $extensible;
		$this->constraints = array();
		foreach($this->model as $i => $r) {
			if (!is_array($r))
				throw new Exception("Table model column specs must be array.");

			if (!isset($r[1]))
				$this->model[$i][1] = '';
		}
	}

	function addRow($values = array())
	{
		if (!is_array($values)) {
			$args = func_get_args();
			$values = array();
			foreach(array_keys($this->model) as $i => $k) {
				$values[$k] = isset($args[$i]) ? $args[$i] : '';
			}
		}

		$row = new Wtk_Form_Model_Instance_Group((string) count($this->value));
		foreach($this->model as $key => $conf) {
			$value = isset($values[$key]) ? $values[$key] : '';

			$args = array_merge(array($this->model[$key][0], // type
						  $key,
						  $this->model[$key][1], // label
						  $value),
					    array_slice($this->model[$key], 2));
			$child = call_user_func_array(array($row, 'addChild'), $args);
		}
		return $this->addChild($row);
	}

	function retrieve ($values)
	{
		$valid = TRUE;
		$saved = $this->value;
		$this->value = array();

		foreach ($values as $i => $tuple) {
			$row = $this->addRow($tuple);
			if ($row) {
				foreach($row as $child) {
					$value = array_key_exists($child->id, $tuple) ? $tuple[$child->id] : null;
					$valid = $valid && $child->retrieve($value);
				}
			}
		}

		if (!$valid)
			$this->values = $saved;
		else
			unset($saved);

		return $valid;
	}
}