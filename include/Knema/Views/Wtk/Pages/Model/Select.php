<?php

class Knema_Pages_Model_Select extends Wtk_Pages_Model
{
	protected $rowcount;

	function __construct(Zend_Db_Select $select, $count= 15, $current = 1)
	{
		// détermine le nombre de ligne
		$cs = clone($select);
		if ($select instanceof Zend_Db_Table_Select) {
			$cs->from($select->getTable(), array('count' => 'COUNT(*)'));
		}
		else {
			$cs->columns(array('count' => 'COUNT(*)'));
		}
		$stmt = $cs->query();
		$row = $stmt->fetch();
		$this->rowcount = $row['count'];
		// détermine les pages
		$this->pages_id = range(1, $this->pagesCount(), 1);
		
		// selection les tuples de cette pages.
		$select->limitPage($current, $count);
		$stmt = $select->query();
		parent::__construct($stmt->fetchAll(), $count, $current);
	}

	function getPagesIds()
	{
		return $this->pages_id;
	}

	function pagesCount()
	{
		return round(($this->rowcount / $this->item_per_page) + .5);
	}

	function getPrevId($ref = null)
	{
		$ref = $ref ? $ref : $this->current;
		$prev = $ref - 1;
		return $prev > 0 ? $prev : null;
	}

	function getNextId($ref = null)
	{
		$ref = $ref ? $ref : $this->current;
		$next = $ref + 1;
		return $next < $this->pagesCount() ? $next : null;
	}

	function fetch($id = null)
	{
		return array_key_exists($id, $this->data) ? $this->data[$id] : null;
	}

	/*
	 * Return le nombre d'item de la page courante
	 */
	public function count()
	{
		return count($this->data);
	}

	/*
	 * réinitialise au début de la page courante.
	 */
	public function rewind()
	{
		reset($this->data);
	}
  
	public function current()
	{
		return current($this->data);
	}

	/*
	 * Retourne la clef relativement à la page courante
	 */
	public function key()
	{
		return key($this->data);
	}

	public function next()
	{
		next($this->data);
	}

	public function valid()
	{
		return $this->current() !== false;
	}
}

