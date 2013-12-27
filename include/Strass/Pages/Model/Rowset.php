<?php

class Strass_Pages_Model_Rowset extends Wtk_Pages_Model
{
	protected $rowcount;

	function __construct(Zend_Db_Table_Select $select, $count= 15, $current = 1)
	{
	  $table = $select->getTable();
	  $this->select = $select;
	  $this->rowcount = $table->countRows($select);

	  // selection les tuples de cette pages.
	  $select->limitPage($current, $count);
	  $rowset = $table->fetchAll($select);

	  parent::__construct($rowset, $count, $current);

	  $this->pages_count = intval(ceil(($this->rowcount / $this->item_per_page)));
	  $this->pages_id = range(1, $this->pagesCount(), 1);
	}

	function pagesCount()
	{
		return $this->pages_count;
	}

	function getPagesIds()
	{
		return $this->pages_id;
	}

	function fetch($id = null)
	{
		return array_key_exists($id, $this->data) ? $this->data[$id] : null;
	}

	function getPrevId($ref = null)
	{
		return $this->getRelId($ref, -1);
	}

	function getNextId($ref = null)
	{
		return $this->getRelId($ref, +1);
	}

	// détermine l'identifiant de la page précédente ou suivante par
	// rapport à $ref;
	protected function getRelId($ref, $sens)
	{
	  $ref = $ref ? $ref : $this->current;
	  $r = array_flip($this->pages_id);
	  $i = $r[$ref]+$sens;
	  return array_key_exists($i, $this->pages_id) ? $this->pages_id[$i] : null;
	}

	public function count()
	{
	  return $this->data->count();
	}

	public function rewind()
	{
	  $this->data->rewind();
	}
  
	public function current()
	{
	  return $this->data->current();
	}

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

