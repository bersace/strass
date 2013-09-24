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


class Dio_Table_CellAddress
{
	protected $_table;
	protected $_column;
	protected $_row;

	function __construct($table, $column, $row)
	{
		$this->_table = $table;
		$this->_column = $column;
		$this->_row = $row;
	}

	function __toString()
	{
		return $this->_table.'.'.$this->_column.$this->_row;
	}
}
