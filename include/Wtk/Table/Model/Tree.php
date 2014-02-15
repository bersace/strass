<?php

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

    call_user_func_array(array('parent', 'append'), $values);
    return $path;
  }
}
