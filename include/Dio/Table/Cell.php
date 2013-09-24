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


class Dio_Table_Cell extends Dio_Element
{
	protected	$_value;
	protected	$_rspan;
	protected	$_cspan;
	protected	$_computedWidth;

	function __construct($index = null, $value = null, $rspan = 1, $cspan = 1)
	{
		parent::__construct('table:table-cell', null, Dio_Document::NS_TABLE);
		$this->_index = $index;
		$this->_value = $value;
		$this->_rspan = max(1,$rspan);
		$this->_cspan = max(1,$cspan);
	}

	function _postAppendChild()
	{
		if ($this->_value)
			$this->_put($this->_value);
		$this->setAttribute('table:number-rows-spanned', $this->_rspan);	
		$this->setAttribute('table:number-columns-spanned', $this->_cspan);
	}

	function put($value)
	{
		$type = $this->_getOfficeType($value);
		if ($type) {
			$this->setAttributeNS(Dio_Document::NS_OFFICE, 'office:value-type', $type);
			$this->_setOfficeValue($value, $type);
		}

		$this->_value = $value;
		$string = $this->getString($value, $type);

		if ($value instanceof DomElement) {
			// Beware that not all Element can be packed
			// in a Cell. We should add an interface
			// "Packable" for that.
			if ($value instanceof Dio_Element)
				$container = $this;
			else
				$container = $this->appendChild(new Dio_Text_P);
			$content = $container->appendChild($value);
			$this->_computedWidth = strlen($value->nodeValue)+2;
		}
		else {
			$content = $this->appendChild(new Dio_Text_P($string));
			$this->_computedWidth = strlen($string)+2;
		}

		if ($this->_cspan > 1)
			$this->_computedWidth = 0;

		return $this;
	}


	protected function _getOfficeType($value)
	{
		$type = gettype($value);
		switch($type) {
		case 'string':
			return 'string';
		case 'integer':
		case 'float':
		case 'double':
			return 'float';
		case 'boolean':
			return 'boolean';
		case 'object':
			// TODO: handle date, time, percentage and currency
			return null;
		default:
			return null;
		}
	}

	protected function _setOfficeValue($value, $type)
	{
		switch($type) {
		case 'currency':
			$this->setAttribute('office:currency', 'EUR');
		case 'float':
		case 'percentage':
			$this->setAttribute('office:value', $value);
			break;
		case 'string':
			$this->setAttribute('office:string-value', $value);
			break;
		case 'bool':
			$this->setAttribute('office:boolean-value', $value ? 'true' : 'false');
			break;
		case 'time':
			$this->setAttribute('office:time-value', $value);
			break;
		case 'date':
			$this->setAttribute('office:date-value', $value);
			break;
		}
	}

	function getString($value, $type)
	{
		switch($type) {
		case 'string':
			return is_string($value) ? $value : null;
		case 'float':
			return intval($value) < $value ? sprintf("%.2f", $value) : sprintf("%d", $value);
		case 'boolean':
			return $value ? 'true' : 'false';
			break;
		}
	}

	function getWidth()
	{
		return $this->_computedWidth;
	}


	function setStyle($style)
	{
		$this->setAttribute('table:style-name', (string)$style);
	}

	function getAddress()
	{
		$row = $this->parentNode;
		$table = $row->parentNode->parentNode;
		$column = $table->childNodes->item(1)->getColumn($this->_index);
		return new Dio_Table_CellAddress($table, $column, $row);
	}
  }
