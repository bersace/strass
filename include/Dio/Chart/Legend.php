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


class Dio_Chart_Legend extends Dio_Element
{
	const POSITION_START	= 'start';
	const POSITION_END	= 'end';
	const POSITION_TOP	= 'top';
	const POSITION_BOTTOM	= 'bottom';

	const POSITION_TOP_START	= 'top-start';
	const POSITION_BOTTOM_START	= 'bottom-start';
	const POSITION_TOP_END		= 'top-end';
	const POSITION_BOTTOM_END	= 'bottom-end';

	const ALIGN_START	= 'start';
	const ALIGN_CENTER	= 'center';
	const ALIGN_END		= 'end';

	// Set align only for START, END, TOP and BOTTOM position.
	function __construct($position, $align = null)
	{
		parent::__construct('chart:legend', null, Dio_Document::NS_CHART);
		$this->setAttribute('chart:legend-position', $position);
		if (in_array($position, array(self::POSITION_START, self::POSITION_END,
					      self::POSITION_TOP, self::POSITION_BOTTOM)))
			$this->setAttribute('chart:legend-align', $align);
	}
  }