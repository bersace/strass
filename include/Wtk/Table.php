<?php

class Wtk_Table extends Wtk_Container
{
  protected $stylecomponent = 'table';
  public $category_column = null;
  public $category_renderer = null;
  public $category_classes = array();

  /*
   * Contruit un tableau représentant $model. $class_col définit
   * la colonne du model définissant le/les marqueur de ligne.
   */
  function __construct ($model, $show_header = true, $class_col = null)
  {
    parent::__construct();
    $this->model = $model;
    $this->show_header = $show_header;
    $this->columns = array();
    $this->class_col = (array)$class_col;
    $this->cclasses = array();
    $this->setRowDojoType(null);
  }

  function _finalize()
  {
    $prev_cat = null;
    foreach($this->model as $i => $tuple) {
      if ($this->category_column) {
	$cat = $tuple[$this->category_column];
	if ($cat != $prev_cat) {
	  $element = $this->category_renderer->element($this->category_renderer->getDataFromTuple($tuple));
	  foreach ($this->category_classes as $k)
	    $element->addFlags($tuple[$k]);
	  $this->addChild($element, 'category-'.$i);
	}
	$prev_cat = $cat;
      }

      foreach ($this->columns as $j => $col) { // pour chaque colonne
	$element = $col->renderer->element($col->renderer->getDataFromTuple($tuple));
	$this->addChild($element, 'cell-'.$i.'-'.$j);
      }
    }
    $this->_finalizeChildren();
  }

  function setRowDojoType($type)
  {
    $this->rowDojoType = $type;
  }

  function getDojoType()
  {
    $djts = array($this->dojoType, $this->rowDojoType);
    foreach($this->children as $child) {
      $djts = array_merge($djts, (array) $child->getDojoType());
    }
    return $djts;
  }

  function getModel()
  {
    return $this->model;
  }

  function addColumn (Wtk_Table_Column $column)
  {
    $cols = $this->columns;
    array_push ($cols, $column);
    $this->columns = $cols;

    $cclasses = $this->cclasses;
    $props = $column->getRenderer()->getProperties();
    $classes = array();
    foreach($props as $prop) {
      if (is_array($prop))
	continue;
      if (!array_key_exists($prop, $this->model->columns))
	continue;
      array_push($classes, wtk_strtoid($prop));
    }
    $classes = array_merge($classes, $column->flags);
    array_push($cclasses, $classes);
    $this->cclasses = $cclasses;
    return $column;
  }

  function addNewColumn()
  {
    $args = func_get_args();
    $col = wtk_new('Wtk_Table_Column', $args);
    $this->addColumn($col);
    return $col;
  }

  function setCategoryColumn($name, $renderer, $class_columns=array()) {
    $this->category_column = $name;
    $this->category_renderer = $renderer;
    $this->category_classes = $class_columns;
  }

  function template ()
  {
    $tpl = $this->elementTemplate ();
    $tpl->addData(array('cols' => count($this->columns),
			'rows' => $this->model->count()));
    $this->addChildrenTemplate($tpl);

    $row = array();
    $cat_classes = array();
    $rclasses = array();
    $class_col = (array) $this->class_col;
    $prev_cat = null;
    // générer les template des cellules
    foreach ($this->model as $i => $tuple) {
      if ($this->category_column) {
	$cat = $tuple[$this->category_column];
	if ($cat != $prev_cat) {
	  $cat_classes[$i] = array('category');
	  foreach ($this->category_classes as $k)
	    $cat_classes[$i][] = $tuple[$k];
	}
	$prev_cat = $cat;
      }
      $row[$i] = array();
      $rclasses[$i] = array();
      foreach($class_col as $col)
	$rclasses[$i][] = $tuple[$col];
    }
    $tpl->addData(array('rclasses' => $rclasses, 'cat_classes' => $cat_classes));
    return $tpl;
  }
}

?>
