<?php

class Wtk_Table_CellRenderer_TreeNode extends Wtk_Table_CellRenderer
{
  public $properties = array('path' => array());

  function __construct($subrenderer)
  {
    $this->subrenderer = $subrenderer;
    $this->properties += $subrenderer->properties;

    $args = array('path', '$$path$$');
    foreach($subrenderer->keys as $k => $v) {
      array_push($args, $k);
      array_push($args, $v);
    }
    call_user_func_array(array('parent', '__construct'), $args);
  }

  function element($data)
  {
    $cell = new Wtk_Table_TreeCell($data['path']);
    $cell->addChild($this->subrenderer->element($data));
    return $cell;
  }
}