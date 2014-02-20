<?php

abstract class Wtk_Pages_Model implements Iterator
{
  public $data;
  public $pages_id;
  public $item_per_page = 15;
  public $current;

	function __construct($data, $item_per_page, $current)
	{
		$this->data		= $data;
		$this->item_per_page	= $item_per_page;
		$this->current		= $current ? $current : 1;
		$this->pages_id		= array();
	}

	function getPagesIds()
	{
		return $this->pages_id;
	}

	function hasPageId($id)
	{
		return in_array($id, $this->pages_id);
	}

	// nombre de pages
	function pagesCount()
	{
		return count($this->data);
	}

	// Retourne l'identifiant de la page actuelle.
	function getCurrentPageId()
	{
		return $this->current;
	}

	// Retourn l'id de la page précédente ou null;
	abstract function getPrevId($ref = null);
	// Retourn l'id de la page suivante ou null;
	abstract function getNextId($ref = null);
	// Retourne un élément de la page
	abstract function fetch($id = null);

	function current()
	{
		return $this->fetch($this->current);
	}}
