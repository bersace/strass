<?php

abstract class Strass_Db_Table_Abstract extends Zend_Db_Table_Abstract
{
  function findBySlug($slug) {
    $s = $this->select()->where('slug = ?', $slug);
    return $this->fetchSelect($s)->current();
  }

	function countRows($select = null)
	{
	  $db = $this->getAdapter();

	  if (is_string($select)) {
	    $where = $select;
	    $select = null;
	  }
	  else {
	    $where = null;
	  }

	  if (!$select) {
	    $select = $db->select()
	      ->distinct();
	  }
	  else {
	    // On clone, car le $select est souvent utilisé pour autre
	    // chose que count après cette fonction
	    $select = clone($select);
	  }

	  $select->from($this->_name, array('count' => 'COUNT(*)'));

	  if ($where) {
	    $select = $select->where($where);
	  }


	  $stmt = $db->query($select->__toString());
	  $res = $stmt->fetch();
	  return $res['count'];
	}


	function fetchSelect($select)
	{
		if (!$select->getPart(Zend_Db_Select::FROM))
			$select->from($this->_name);

		$stmt = $this->getAdapter()->query($select->__toString());
		$data = array('table'	=> $this,
			      'data'	=> $stmt->fetchAll(),
			      'rowClass'	=> $this->_rowClass,
			      'stored'	=> true);
		return new $this->_rowsetClass($data);
	}
}