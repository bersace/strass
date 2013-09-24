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


class Dio_Chart_PlotArea extends Dio_Chart_Element {
	const LABELS_NONE	= 'none';
	const LABELS_ROW	= 'row';
	const LABELS_COLUMN	= 'column';
	const LABELS_BOTH	= 'both';

	function __construct($x, $y, $width, $height, $labels = null, $range = null)
	{
		parent::__construct('chart:plot-area', null, Dio_Document::NS_CHART);
		$this->setAttribute('svg:x', $x);
		$this->setAttribute('svg:y', $y);
		$this->setAttribute('svg:width', $width);
		$this->setAttribute('svg:height', $height);
		$this->setAttribute('table:cell-range-address', $range);
		$this->setAttribute('chart:data-source-has-labels', $labels);
	}
  }