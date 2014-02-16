<?php

  // Values of Group are child instances.
class Wtk_Form_Model_Instance_Group extends Wtk_Form_Model_Instance implements Iterator, Countable
{
	function __construct ($path, $label = NULL)
	{
		parent::__construct ($path, $label, array ());
	}

	function setPath ($path)
	{
		parent::setPath ($path);
		foreach ($this->value as $child) {
			$child->setPath ($this->path.'/'.$child->id);
		}
	}

	function &addChild ($instance)
	{
		if (is_string ($instance)) {
			$args = func_get_args();
			$name = array_shift($args);
			$instance = call_user_func_array(array($this, 'add'.$name), $args);
			return $instance;
		}

		if (!($instance instanceof Wtk_Form_Model_Instance))
			Orror::kill($instance);
		$instance->setPath ($this->path.'/'.$instance->id);
		$this->value[$instance->id] = $instance;
		return $instance;
	}

	function &getChild ($path)
	{
		if (!$path) {
			return $this;
		}

		$ids = explode ('/', $path);
		$needle = array_shift ($ids);

		if (isset ($this->value[$needle])) {
			if ($this->value[$needle] instanceof Wtk_Form_Model_Instance_Group) {
				$path = implode ('/', $ids);
				return $this->value[$needle]->getChild($path);
			}
			else {
				return $this->value[$needle];
			}
		}
		else {
		  throw new Exception("Child ".$path." not found");
		}
	}

	function __call($method, $args)
	{
		if (preg_match("/^add(.*)$/", $method, $matches)) {
			$class = "Wtk_Form_Model_Instance_".$matches[1];
			$cargs = wtk_args_string('args', $args);
			$code = "return new ".$class."(".implode(', ', $cargs).");";
			$ins = eval($code);
			return $this->addChild($ins);
		}
	}

	function get ($path = null)
	{
		if ($path) {
			return $this->getChild($path)->get();
		}

		$values = array ();
		foreach ($this->value as $id => $child) {
			if ($id !== '$$validated$$') { // needs !== otherwise '0' is swallowed.
				$values[$id] = $child->get();
			}
		}
		return $values;
	}

	function __get($path)
	{
		return $this->get($path);
	}

	function hasInstanceByType($class)
	{
		$res = false;
		foreach($this as $child) {
			$res = $res
				|| ($child instanceof $class
				    || ($child instanceof Wtk_Form_Model_Instance_Group && $child->hasInstanceByType($class)));
		}
		return $res;
	}

	function retrieve ($values)
	{
		$valid = FALSE;

		foreach ($this->value as $id => $child) {
			$chval = $this->value[$id]->retrieve (isset ($values[$id]) ? $values[$id] : NULL);
			$valid = $chval && $valid;
		}
		return $valid;
	}



	// ITERATOR, COUNTABLE
	public function count()	{ return count($this->value); }
	public function rewind()	{ return reset($this->value); }
	public function current()	{ return current ($this->value); }
	public function key()		{ return key ($this->value); }
	public function next()	{ return next ($this->value); }
	public function valid()	{ return $this->current () !== false; }
}

?>