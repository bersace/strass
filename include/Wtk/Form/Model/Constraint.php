<?php

abstract class Wtk_Form_Model_Constraint
{
	protected	$instance;
	protected	$script;
	protected	$message = "%s erroné.";

	function __construct ($instance, $message = null)
	{
		$this->instance = $instance;
		if ($message)
			$this->message = $message;
	}

	protected function getScript()
	{
		return null;
	}

	protected function getFlag()
	{
		$class = get_class($this);
		$parts = explode('_', $class);
		$id = array_pop($parts);
		$parts = array_map("strtolower", $parts);
		array_push($parts, $id);
		return array(strtolower($id), implode(".", $parts));
	}

	function getScripts()
	{
		return array();
	}

	function getFlags()
	{
		return array($this->getFlag());
	}

	function getInstance()
	{
		return $this->instance;
	}

	function validate ()
	{
		$this->instance->valid = $v = $this->_validate($this->instance->get());
		if (!$v)
			throw new Wtk_Form_Model_Exception($this->message, $this->instance);

		return $v;
	}

	function _validate()
	{
		return true;
	}
}
