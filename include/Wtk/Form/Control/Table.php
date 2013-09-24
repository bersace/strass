<?php

class Wtk_Form_Control_Table extends Wtk_Form_Control implements Wtk_Container_Interface
{
	protected	$model;
	protected	$table;

	function __construct ($instance, $model)
	{
		parent::__construct ($instance);

		// récupération des colonnes
		$row = $instance->rewind();
		$columns = array();
		if ($row) {
			foreach($row as $cell)
				$columns[$cell->id] = $cell->label;
		}

		// création du model du tableau
		$this->tm = new Wtk_Table_Model($columns);
		foreach($instance as $row) {
			$args = array();
			foreach($row as $cell) {
				$args[] = $cell;
			}
			call_user_func_array(array($this->tm, 'append'), $args);
		}

		// création de la vue
		$this->table = new Wtk_Table($this->tm);
		$this->table->addFlags('form', 'control');
		$this->table->setDojoType("wtk.form.control.Table");
		$this->table->setRowDojoType("wtk.form.control.TableRow");

		if ($instance->reorderable)
			$this->table->addFlags('reorderable');
		if ($instance->extensible)
			$this->table->addFlags('extensible');

		if ($row) {
			foreach ($model as $name => $conf) {
				$class = $conf[0];
				$args = array_slice($conf, 1);
				$this->table->addColumn(new Wtk_Table_Column($columns[$name], // arg
									     new Wtk_Table_CellRenderer_Control($class,
														$args,
														$name)));
			}
		}
	}

	function getDojoType()
	{
		return $this->table->getDojoType();
	}

	/**
	 * retourne la liste récursive des composants de style du
	 * conteneur et de ses enfants.
	 */
	function getStyleComponents()
	{
		return $this->table->getStyleComponents();
	}


	function _finalize()
	{
		$this->table->finalize();
	}

	function template()
	{
		return $this->table->template();
	}

	function getStyleComponent()
	{
		return $this->table->getStyleComponent();
	}
}
