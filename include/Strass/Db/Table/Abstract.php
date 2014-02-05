<?php

class Strass_Db_Table_Multiple extends Zend_Db_Table_Exception {}
class Strass_Db_Table_NotFound extends Zend_Db_Table_Exception {}

abstract class Strass_Db_Table_Abstract extends Zend_Db_Table_Abstract
{
  function findBySlug($slug) {
    $s = $this->select()->where($this->_name.'.slug = ?', $slug);
    return $this->fetchAll($s)->current();
  }

  function findOne() {
    $key = func_get_args();
    $res = call_user_func_array(array($this, 'find'), $key);
    $row = $res->current();

    if (!$row) {
      throw new Strass_Db_Table_NotFound;
    }

    return $row;
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

  function fetchAll($where = null, $order = null, $count = null, $offset = null) {
    if (is_null($where) || is_string($where) || $where instanceof Zend_Db_Table_Select)
      return parent::fetchAll($where, $order, $count, $offset);
    else if (!$where instanceof Zend_Db_Select)
      throw new Exception("Mauvais type de requête");

    $select = $where;
    $rows = $this->_fetch($select);

    $data = array('table'    => $this,
		  'data'     => $rows,
		  'readOnly' => false,
		  'rowClass' => $this->_rowClass,
		  'stored'   => true
		   );

    if (!class_exists($this->_rowsetClass)) {
      require_once 'Zend/Loader.php';
      Zend_Loader::loadClass($this->_rowsetClass);
    }

    return new $this->_rowsetClass($data);
  }

  function fetchOne($select) {
    $select->limit(2);
    $all = $this->fetchAll($select);

    if ($all->count() > 1) {
      throw new Strass_Db_Table_Multiple;
    }
    else if ($all->count() == 0) {
      throw new Strass_Db_Table_NotFound();
    }
    else {
      return $all->current();
    }
  }
}