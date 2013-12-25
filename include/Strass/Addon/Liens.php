<?php


  /**
   * Rassemble des liens
   */
class Strass_Addon_Liens extends Strass_Addon implements Iterator, Countable
{
	protected $liens;
	protected $id;
	protected $titre;

	function __construct($id, $titre)
	{
		$this->liens = array();
		$this->id = $id;
		$this->titre = $titre;
	}

	protected function lien($metas, array $urlOptions = array(), array $acl = array(), $reset = false)
	{
		if ($acl && $acl[0] == null)
			$acl[0] = Zend_Registry::get('user');

		if (!$metas)
			return false;
    
		if ($acl && count($acl) < 3 && isset($urlOptions['action']))
			array_push($acl, $urlOptions['action']);

		if (!is_array($metas))
			$metas = array('label' => $metas);
    
		return array('metas' => $metas,
			     'urlOptions' => $urlOptions,
			     'acl' => $acl,
			     'reset' => $reset);
	}

	function append($metas, array $urlOptions = array(), array $acl = array(), $reset = false)
	{
		if ($lien = $this->lien($metas, $urlOptions, $acl, $reset))
			$this->liens[] = $lien;
	}

	/*
	 * Insert un lien à la position $pos. Si $pos est négatif, la
	 * position est compté à partir de la fin.
	 */
	function insert($pos, $metas, array $urlOptions = array(), array $acl = array(), $reset = false)
	{
		$count = count($this->liens);
		if (!$lien = $this->lien($metas, $urlOptions, $acl, $reset))
			return;

		if ($pos < 0)
			$pos = $count + $pos;

		if ($pos < $count) {
		  for($i = $pos; $i < $count ; $i++) {
		    $sauf = $this->liens[$i];
		    $this->liens[$i] = $lien;
		    $lien = $sauf;
		  }
		}
		$this->liens[] = $lien;
	}

	function initView ($view)
	{
		$acl = Zend_Registry::get('acl');
		$view->liens = array();
		$view->id = $this->id;
		$view->titre = $this->titre;
		foreach($this->liens as $lien) {
			if (!$lien['acl'] || call_user_func_array(array($acl, 'isAllowed'), $lien['acl'])) {
				$view->liens[] = $lien;
			}
		}
	}
  
	function viewScript()
	{
		$c = explode('_', __CLASS__);
		return strtolower($c[2]);
	}

	public function count()	{ return count($this->liens); }
	public function rewind()	{ return reset($this->liens); }
	public function current()	{ return current($this->liens); }
	public function key()		{ return key($this->liens); }
	public function next()	{ return next($this->liens); }
	public function valid()	{ return $this->current() !== false; }
}
