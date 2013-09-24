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


class Dio_Chart extends Dio_Chart_Element
{
	const TYPE_LINE		= 'chart:line';
	const TYPE_AREA		= 'chart:area';
	const TYPE_CIRCLE	= 'chart:circle';
	const TYPE_RING		= 'chart:ring';
	const TYPE_SCATTER	= 'chart:scatter';
	const TYPE_RADAR	= 'chart:radar';
	const TYPE_BAR		= 'chart:bar';
	const TYPE_STOCK	= 'chart:stock';
	const TYPE_BUBBLE	= 'chart:bubble';
	const TYPE_SURFACE	= 'chart:surface';
	const TYPE_GANT		= 'chart:gant';

	protected $_class;
	protected $_title;
	protected $_width;
	protected $_height;

	function __construct($class, $width = "100%", $height = "100%", $title = null)
	{
		parent::__construct('chart:chart', null, Dio_Document::NS_CHART);
		$this->_class = $class;
		$this->_width = $width;
		$this->_height= $height;
		$this->_title = $title;
	}

	function _postAppendChild()
	{
		$this->setAttribute('chart:class', $this->_class);
		$this->setAttribute('svg:width', $this->_width);
		$this->setAttribute('svg:height', $this->_height);
		$this->setAttribute('chart:title', $this->_title);
	}
  }
