<?php

class Wtk_Table_Model implements Iterator, Countable
{
	public $columns;
	public $rows;

	// $columns est un tableau de clef=>valeur (chaîne ou null)
	// ou une liste de clef.
	function __construct ($columns)
	{
		if (is_array($columns))
			$this->columns = $columns;
		else {
			$keys = func_get_args();
			$this->columns = array_combine($keys, array_fill(0, count($keys), null));
		}
		$this->rows = array();
	}

	function getColumns()
	{
		return $this->columns;
	}

	function getColumnIds()
	{
		return array_keys($this->columns);
	}

	function append ($value0)
	{
		$values = func_get_args();
		$row = array();
		$ids = $this->getColumnIds();
		foreach ($ids as $i => $col)
			$row[$col] = array_key_exists($i, $values) ? $values[$i] : null;
		array_push ($this->rows, $row);
	}

	// ITERATOR
	public function count ()
	{
		return count ($this->rows);
	}

	public function rewind() {
		return reset($this->rows);
	}

	public function current() {
		return current($this->rows);
	}

	public function key() {
		return key($this->rows);
	}

	public function next() {
		return next($this->rows);
	}

	public function valid() {
		return $this->current() !== false;
	}
}
