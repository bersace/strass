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


class Dio_Table_Column extends Dio_Element
{
	protected	$_index;
	protected	$_letters;
	protected	$_id;
	protected	$_style;
	protected	$_width = 0;

	function __construct($index, $name="")
	{
		static $count = 0;
		parent::__construct('table:table-column', null, Dio_Document::NS_TABLE);
		$this->_index = $index;
		$this->_id = ++$count;
		$this->_width= strlen($name)+4;
	}

	function _postAppendChild()
	{
		$styleName = "co".$this->_id;

		$as = $this->ownerDocument->astyles;
		$this->_style = $as->addStyle($styleName, Dio_Style::FAMILY_TABLE_COLUMN);
		$this->setAttribute('table:style-name', (string) $this->_style);
		$this->setAttribute('table:default-cell-style-name', 'Default');

		$ps = $this->_style->addProperties(new Dio_Style_Properties_TableColumn);
		$ps->useOptimalColumnWidth(true);
	}

	function newCellWidth($width)
	{
		$this->_width = max($this->_width, $width);
		$this->_style->propertySets['style:table-column-properties']->setWidth(($this->_width/6.).'cm');
	}

	function __toString()
	{
		static $letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

		if (!$this->_letters) {
			$this->_letters = "";
			$index = $this->_index;
			$base = strlen($letters);
			$i = 1;
			do {
				$this->_letters = $letters{$index % $base}.$this->_letters;
				$index = intval($index/$base);;
				$i++;
			} while($index);
		}

		return $this->_letters;
	}
  }
