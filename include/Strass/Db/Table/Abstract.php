<?php

class Strass_Db_Table_Multiple extends Zend_Db_Table_Exception {}
class Strass_Db_Table_NotFound extends Zend_Db_Table_Exception {}

abstract class Strass_Db_Table_Abstract extends Zend_Db_Table_Abstract
{
  static $_rowCache = null;
  protected $_rowsetClass = 'Strass_Db_Table_Rowset';

  function getColumns()
  {
    return $this->_cols;
  }

  function createSlug($base, $current=null)
  {
    $base = wtk_strtoid($base);

    $s = $this->getAdapter()->select()
      ->from($this->_name, array('slug'))
      ->where('slug LIKE ?', $base.'%');

    if ($current)
      $s->where('slug <> ?', $current);

    $r = $this->getAdapter()->query($s)->fetchAll();
    $existants = array();
    foreach($r as $row)
      array_push($existants, $row['slug']);

    $i = 0;
    do {
      $candidat = $base;
      if ($i)
	$candidat.= '-'.$i;

      if (in_array($candidat, $existants))
	$i++;
      else
	return $candidat;
    } while(true);
  }

  function findBySlug($slug) {
    $s = $this->select()->where($this->_name.'.slug = ?', $slug);
    return $this->fetchOne($s);
  }

  function findOne() {
    $key = func_get_args();
    $res = call_user_func_array(array($this, 'find'), $key);
    $row = $res->current();

    if (!$row) {
      $key = implode(', ', (array) $key);
      throw new Strass_Db_Table_NotFound("No row for ${key}");
    }

    return $row;
  }

  function countRows($select = null)
  {
    $db = $this->getAdapter();

    if (!$select) {
      $select = $this->select();
    }
    else {
      // On clone, car le $select est souvent utilisé pour autre
      // chose que count après cette fonction
      $select = clone($select);
    }

    try {
      $select->columns(array('count' => 'COUNT(*)'));
    }
    catch (Zend_Db_Select_Exception $e) {
      $select->from($this->_name, array('count' => 'COUNT(*)'));
    }

    $res = $select->query()->fetch();
    return intval($res['count']);
  }

  function fetchAll($where = null, $order = null, $count = null, $offset = null) {
    if (!is_object($where) || $where instanceof Zend_Db_Table_Select)
      return parent::fetchAll($where, $order, $count, $offset);
    else if (!$where instanceof Zend_Db_Select)
      throw new Exception("Mauvais type de requête");
    else
      throw new Exception("Qui sait traiter ça ? ".gettype($where));
  }

  function fetchOne($select) {
    $select->limit(2);
    $all = $this->fetchAll($select);

    if ($all->count() > 1) {
      throw new Strass_Db_Table_Multiple("Multiple row found for ". (string) $select);
    }
    else if ($all->count() == 0) {
      throw new Strass_Db_Table_NotFound("No row for ".(string) $select);
    }
    else {
      return $all->current();
    }
  }

  function fetchFirst($select) {
    $select->limit(1);
    $all = $this->fetchAll($select);

    if ($all->count() == 0) {
      throw new Strass_Db_Table_NotFound("No row for ".(string) $select);
    }
    else {
      return $all->current();
    }
  }
}
