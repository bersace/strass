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


class Dio_Chart_Axis extends Dio_Chart_Element
{
	const DIMENSION_X = 'x';
	const DIMENSION_Y = 'y';
	const DIMNESION_Z = 'z';

	function __construct($dimension, $name)
	{
		parent::__construct('chart:axis', null, Dio_Document::NS_CHART);
		$this->setAttribute('chart:dimension', $dimension);
		$this->setAttribute('chart:name', $name);
	}

	function __toString()
	{
		return $this->getAttribute('chart:name');
	}
  }