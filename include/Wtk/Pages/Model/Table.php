<?php

class Wtk_Pages_Model_Table extends Wtk_Pages_Model
{
	protected $rowcount;

	function __construct($table, $where = array(), $order = array(), $count= 15, $current = 1)
	{
		$db = $table->getAdapter();
		$info = $table->info();

		// select total row count
		$select = $db->select()
			->from($info[Zend_Db_Table_Abstract::NAME],
			       array('count' => 'COUNT(*)'));
		if ($where)
			$select->where($where);

		$stmt = $db->query($select->__toString());
		$this->rowcount = $stmt->fetchColumn();

		// select current page rows
		$current = $current ? $current : 1;
		$offset = ($current-1) * $count;
		$data = $table->fetchAll($where, $order, $count, $offset);
		parent::__construct($data, $count, $current);

		$page_count = intval(ceil($this->rowcount/$this->item_per_page));
		$this->pages_id = $this->rowcount ? range(1, $page_count) : array();
	}

	function getPagesIds()
	{
		return $this->pages_id;
	}

	function pagesCount()
	{
		return intval(($this->rowcount / $this->item_per_page) + .5);
	}

	function getPrevId($ref = null)
	{
		return $this->getRelId($ref, -1);
	}

	function getNextId($ref = null)
	{
		return $this->getRelId($ref, +1);
	}

	protected function getRelId($ref, $sens)
	{
		$ref = $ref ? $ref : $this->current;
		$r = array_flip($this->pages_id);
		$i = $r[$ref]+$sens;
		return array_key_exists($i, $this->pages_id) ? $this->pages_id[$i] : null;
	}

	function fetch($id = null)
	{
		$id = $id ? $id : $this->getCurrentPageId();
		$this->data->rewind();
		for ($i = 0; $i < $id; $i++)
			$this->data->next();

		return $this->data->current();
	}


	/*
	 * Return le nombre d'item de la page courante
	 */
	public function count()
	{
		return $this->data->count();
	}

	/*
	 * réinitialise au début de la page courante.
	 */
	public function rewind()
	{
		$this->data->rewind();
	}
  
	public function current()
	{
		return $this->data->current();
	}

	/*
	 * Retourne la clef relativement à la page courante
	 */
	public function key()
	{
		return $this->data->key();
	}

	public function next()
	{
		$this->data->next();
	}

	public function valid()
	{
		return $this->data->valid();
	}
}

