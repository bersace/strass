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


class Dio_Table_Rows extends Dio_Element
{
	protected $_rows = array();

	function __construct($header = false)
	{
		$tag = 'table:table-'.($header ? 'header-' : '').'rows';
		parent::__construct($tag, null, Dio_Document::NS_TABLE);
	}

	function addRow()
	{
		return $this->_rows[] = $this->addTable_Row(count($this->_rows));
	}

	function getRow($index)
	{
		return array_key_exists($index, $this->_rows) ?$this->_rows[$index] : null; 
	}
  }
