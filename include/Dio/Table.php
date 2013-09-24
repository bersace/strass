<?php
/* Dio - PHP OpenDocument Generator
 * Copyright (C) 2008-2009  Étienne BERSAC <bersace03@gmail.com>
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License
 * as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this program.  If not, see
 * <http://www.gnu.org/licenses/>.
 */


class Dio_Table extends Dio_Element
{

	protected	$_columnNames;
	protected	$_hrows;
	protected	$_hcolumns;
	protected 	$_rows;
	protected	$_columns;

	function __construct($header0=null)
	{
		static $count = 0;

		parent::__construct('table:table', null, Dio_Document::NS_TABLE);
		$headers = is_array($header0) ? $header0 : func_get_args();
		$this->_columnNames = (array) $headers;
		$this->_id = ++$count;
	}

	function _postAppendChild()
	{
		$this->_hcolumns = $this->addTable_Header_Columns();
		$this->_columns = $this->addTable_Columns();

		$this->_hrows = $this->addTable_Header_Rows();
		$this->_rows = $this->addTable_Rows();

		$r = $this->_hrows->addRow();
		foreach($this->_columnNames as $i => $name) {
			$this->_hcolumns->addColumn($name);
			$this->_columns->addColumn();
			$r->put($i, 0, $name);
		}
	}

	/*
	 * @row	integer|couple	row id or (rowstart,rowend)
	 * @col	integer|couple	col id or (colstart,colend)
	 */
	function put($col, $row, $value)
	{
		list($cmin, $cmax) = is_array($col) ? $col : array($col,$col);

		// add columns before target column
		for ($i = 0; $i <= $cmin; $i++)
			if (!$c = $this->_columns->getColumn($i))
				$this->_columns->addColumn();

		list($rmin, $rmax) = is_array($row) ? $row : array($row,$row);
		// Add rows before target row
		for ($i = 0; $i <= $rmin; $i++)
			if (!$r = $this->_rows->getRow($i))
				$r = $this->_rows->addRow();

		$cell = $this->_rows->getRow($rmin)->put($col, $row, $value);

		// Hack for pseudo auto width.
		$width = $this->get($cmin, $rmin)->getWidth();
		$this->_columns->getColumn($cmin)->newCellWidth($width);

		return $cell;
	}

	function get($col_index, $row_index)
	{
		if (!$row = $this->_rows->getRow($row_index))
			throw new Exception("Table ".$this." has no row ".$row_index);

		return $row->getCell($col_index);
	}

	function __get($name)
	{
		switch($name) {
		case 'headerRows':
			return $this->_hrows;
		case 'headerColumns':
			return $this->_hcolumns;
		case 'rows':
			return $this->_rows;
		case 'columns':
			return $this->_columns;
		case 'name':
			return $this->getAttribute('table:name');
		default:
			return parent::__get($name);
		}
	}

	function __set($name, $value)
	{
		switch($name) {
		case 'name':
			$this->setAttribute('table:name', $name);
			break;
		default:
			$this->$name = $value;
			break;
		}
	}

	function __toString()
	{
		return $this->name;
	}
  }