<?php

class Wtk_Table extends Wtk_Container
{
	protected $stylecomponent = 'table';

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
		foreach($this->model as $i => $tuple) {
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
		array_push($cclasses, $column->getRenderer()->getProperties());
		$this->cclasses = $cclasses;
		return $column;
	}

	function addNewColumn($title, $renderer)
	{
		$col = new Wtk_Table_Column($title, $renderer);
		$this->addColumn($col);
		return $col;
	}

	function template ()
	{
		$tpl = $this->elementTemplate ();
		$tpl->addData(array('cols' => count($this->columns),
				    'rows' => $this->model->count()));
		$this->addChildrenTemplate($tpl);

		$row = array();
		$rclasses = array(); 
		$class_col = (array) $this->class_col;
		// générer les template des cellules
		foreach ($this->model as $i => $tuple) {
			$row[$i] = array();
			$rclasses[$i] = array();
			foreach($this->class_col as $col)
				$rclasses[$i][] = $tuple[$col];
		}
		$tpl->addData(array('rclasses' => $rclasses));
		return $tpl;
	}
}

?>
