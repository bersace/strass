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


  // Abstract base class for chart element allow to call addElement
  // instead of addChart_Element. Just a fast path.
abstract class Dio_Chart_Element extends Dio_Element
{
	function __call($method, $args)
	{
		if (preg_match("`(add|append|embed)(.*)`", $method, $match)) {
			$class = "Dio_Chart_".$match[2];
			if (class_exists($class))
				$method = $match[1]."Chart_".$match[2];
		}
		return parent::__call($method, $args);
	}
  }
