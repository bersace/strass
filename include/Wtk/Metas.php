<?php

class Wtk_Metas implements Iterator
{
	protected	$config;

	function __construct($config)
	{
		$this->config = $config;
	}

	public function toArray()
	{
		return $this->config;
	}

	function has($field)
	{
		return array_key_exists($field, $this->config);
	}

	function get($field = null)
	{
		if ($field === null)
			return $this->config;
		else
			return $this->has($field) ? $this->config[$field] : NULL;
	}

	function set ($field, $value)
	{
		$this->config[$field] = $value;
	}

	function merge($metas)
	{
		foreach ($metas->toArray () as $field => $value) {
			$this->$field = $value;
		}
	}

	function __get($field)
	{
		return $this->get($field);
	}

	function __set($field, $value)
	{
		$this->set($field, $value);
	}
	// ITERATOR, COUNTABLE
	public function count()
	{
		return count($this->config);
	}
	public function rewind()
	{
		return reset($this->config);
	}
	public function current()
	{
		return current($this->config);
	}
	public function key()
	{
		return key($this->config);
	}
	public function next()
	{
		return next($this->config);
	}
	public function valid()
	{
		return $this->current() !== false;
	}
}
