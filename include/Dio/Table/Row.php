<?php
/* Dio - PHP OpenDocument Generator
 * Copyright (C) 2008  Étienne BERSAC <bersace03@gmail.com>
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


class Dio_Table_Row extends Dio_Element
{
	protected $_cells;
	protected $_id;
	protected $_index;
	protected $_style;

	function __construct($index = null)
	{
		static $count = 0;
		parent::__construct('table:table-row', null, Dio_Document::NS_TABLE);
		$this->_id = ++$count;
		$this->_index = $index;
		$this->_cells = array();
	}

	function _postAppendChild()
	{
		$styleName = "ro".$this->_id;

		$as = $this->ownerDocument->astyles;
		$this->_style = $as->addStyle($styleName, Dio_Style::FAMILY_TABLE_COLUMN);
		$this->setAttribute('table:style-name', (string) $this->_style);
	}

	function put($col, $row, $value)
	{
		list($cmin, $cmax) = is_array($col) ? $col : array($col,$col);
		list($rmin, $rmax) = is_array($row) ? $row : array($row,$row);
		for ($i = 0; $i <= $cmin; $i++) {
			if (!isset($this->_cells[$i])) {
				$this->_cells[$i] = ($i == $cmin) ? new Dio_Table_Cell($i, null, $rmax-$rmin, $cmax-$cmin) : new Dio_Table_Cell;
				$this->appendChild($this->_cells[$i]);
			}
		}

		return $this->_cells[$cmin]->put($value);
	}

	function getCell($index)
	{
		return array_key_exists($index, $this->_cells) ? $this->_cells[$index] : null;
	}

	function __get($name)
	{
		switch($name) {
		case 'cols':
		case 'columns':
		case 'cells':
			return $this->_cells;
		}
	}

	function __toString()
	{
		$table = $this->parentNode->parentNode;
		// Ugly :)
		$header_rows_count = $table->headerRows->childNodes->length;
		return sprintf("%d", $this->_index + 1 + $header_rows_count);
	}
  }
