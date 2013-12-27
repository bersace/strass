<?php

class Strass_Db_Table_Multiple extends Zend_Db_Table_Exception {}
class Strass_Db_Table_NotFound extends Zend_Db_Table_Exception {}

abstract class Strass_Db_Table_Abstract extends Zend_Db_Table_Abstract
{
  function findBySlug($slug) {
    $s = $this->select()->where('slug = ?', $slug);
    return $this->fetchAll($s)->current();
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

  function fetchOne($select) {
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