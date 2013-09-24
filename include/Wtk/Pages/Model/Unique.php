<?php

  /**
   * Implémente l'itération d'un model avec un seul élément par page.
   */
abstract class Wtk_Pages_Model_Unique extends Wtk_Pages_Model
{
	public function count()
	{
		return 1;
	}

	/*
	 * réinitialise au début de la page courante.
	 */
	public function rewind()
	{
		$this->pointer = $this->current;
	}
  
	public function current()
	{
		return $this->fetch($this->pointer);
	}

	/*
	 * Retourne la clef relativement à la page courante
	 */
	public function key()
	{
		return $this->pointer;
	}

	public function next()
	{
		$this->pointer = null;
	}

	public function valid()
	{
		return in_array($this->pointer, $this->pages_id);
	}
  }