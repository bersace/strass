<?php

/* Retourne 1 si $a est le cadet ou le fils de $b, -1 s'il est le père ou l'ainé. */
function wtk_table_tree_path_cmp($a, $b)
{
  $max = max(count($a), count($b));

  for ($i = 0; $i < $max; $i++) {
    if (!array_key_exists($i, $a))
      return 1;
    if (!array_key_exists($i, $b))
      return -1;

    if ($cmp = $a[$i] - $b[$i])
      return $cmp;
  }

  return 0;
}

/*
  Chaque ligne est référencée par un $$path$$. Un path est une liste
  des index de chaque nœuds parent et de la feuille. Il faut désigner
  le chemin du parent pour ajouter dans l'arbre.

  La racine a le chemin array(); Le première ligne a le chemin
  array(0), sa première fille a le chemin array(0, 0), sa deuxième
  array(0, 1), etc.

 */
class Wtk_Table_Model_Tree extends Wtk_Table_Model
{
  function __construct($column0)
  {
      $columns = func_get_args();
      array_unshift($columns, '$$path$$');
      call_user_func_array(array('parent', '__construct'), $columns);
      $this->compteurs = array();
  }

  function append($parent_path, $value0)
  {
    $values = func_get_args();

    $pkey = join('-', $parent_path);
    if (array_key_exists($pkey, $this->compteurs)) {
      $child_id = $this->compteurs[$pkey] + 1;
    }
    else {
      $child_id = 0;
    }
    $this->compteurs[$pkey] = $child_id;

    $path = $parent_path;
    array_push($path, $child_id);
    $values[0] = $path;

    $row = array();
    $ids = $this->getColumnIds();
    foreach ($ids as $i => $col)
      $row[$col] = array_key_exists($i, $values) ? $values[$i] : null;

    $count = count($this->rows);
    $patha = $path;
    $added = false;
    for($i = 0; $i < $count; $i++) {
      $pathb = $this->rows[$i]['$$path$$'];
      if (wtk_table_tree_path_cmp($patha, $pathb) > 0) {
	  array_splice($this->rows, $i, 0, array($row));
	  $added = true;
	  break;
      }
    }
    if (!$added)
      array_push($this->rows, $row);

    return $path;
  }
}
