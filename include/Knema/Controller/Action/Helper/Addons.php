<?php

class Knema_Controller_Action_Helper_Addons extends Zend_Controller_Action_Helper_Abstract implements Iterator, Countable
{
	protected	$addons;

	public function init()
	{
		$this->addons = array();
	}

	public function direct()
	{
		return $this;
	}

	public function append(Knema_Addon $addon)
	{
		$this->addons[] = $addon;
		return $addon;
	}

	public function insert($pos, Knema_Addon $addon)
	{
		$addons = array();
		foreach($this->addons as $i => $a) {
			if ($i == $pos)
				$addons[] = $addon;
			$addons[] = $a;
		}
		$this->addons = $addons;
		return $addon;
	}

	public function render($view, $viewSuffix)
	{
	}

}
