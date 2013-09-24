<?php

class Wtk_Pages_Model_Iterator extends Wtk_Pages_Model
{
	protected	$pages_id;
	protected	$offset;

	function __construct(Iterator $data, $item_per_page = 15, $current = 1)
	{
		parent::__construct($data, $item_per_page, $current);
		$this->pages_count		= max(1,round(($data->count()/$item_per_page)+.49));
		// we need integer to use array_flip.
		$this->pages_id		= array_map('intval', range(1, $this->pages_count));
		$this->offset		=  ($this->current-1) * $this->item_per_page;
		$this->current = min(max(1, intval($current)), $this->pages_count);
	}

	function pagesCount()
	{
		return $this->pages_count;
	}

	function getPagesIds()
	{
		return $this->pages_id;
	}

	function getCurrentPageId()
	{
		return $this->current;
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

	function fetch($id = null)
	{
		$id = $id ? $id : $this->getCurrentPageId();
		return $this->data[$id];
	}

	/*
	 * Return le nombre d'item de la page courante
	 */
	public function count()
	{
		return min($this->item_per_page,
			   $this->data->count() - $this->offset);
	}

	/*
	 * réinitialise au début de la page courante.
	 */
	public function rewind()
	{
		$this->data->rewind();
		$s = ($this->current - 1) * $this->item_per_page;
		for($i = 0; $i < $s; $i++) {
			$this->data->next();
		}
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
		return $this->data->key() - $this->offset;
	}

	public function next()
	{
		$this->data->next();
	}

	public function valid()
	{
		return $this->key() < $this->count();
	}
}

