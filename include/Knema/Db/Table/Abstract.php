<?php

abstract class Knema_Db_Table_Abstract extends Zend_Db_Table_Abstract
{
	function countRows($where = null)
	{
		$db = $this->getAdapter();
		$select = $db->select()
			->distinct()
			->from($this->_name, array('count' => 'COUNT(*)'));
		if ($where) { 
			$select->where($where);
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