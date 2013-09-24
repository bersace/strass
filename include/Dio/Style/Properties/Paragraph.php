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

class Dio_Style_Properties_Paragraph extends Dio_Style_Properties
{
	function __construct()
	{
		parent::__construct('style:paragraph-properties');
	}

	function setTabStopDistance($distance)
	{
		$this->setAttribute('style:tab-stop-distance', $distance);
	}

	function setMarginTop($margin)
	{
		$this->setAttribute('fo:margin-top', $margin);
	}

	function setMarginRight($margin)
	{
		$this->setAttribute('fo:margin-right', $margin);
	}

	function setMarginBottom($margin)
	{
		$this->setAttribute('fo:margin-bottom', $margin);
	}

	function setMarginLeft($margin)
	{
		$this->setAttribute('fo:margin-left', $margin);
	}

	/*
	 * Like margin: property in CSS : (margins|top-bottom right-left|top right-left bottom|top right left bottom).
	 */
	function setMargins($margins)
	{
		$args = func_get_args();
		switch(count($args)) {
		case 1:
			$top = $right = $bottom = $left = $args[0];
		case 2:
			$right = $left = $args[1];
		case 3:
			$bottom = $args[2];
		case 4:
			$left = $args[3];
			break;
		default:
			throw new Dio_PropertyValueInvalid_Exception("Too many margins defined : ".implode(", ", $args).".");
		}

		$this->setMarginTop($top);
		$this->setMarginRight($right);
		$this->setMarginBottom($bottom);
		$this->setMarginLeft($left);
	}

	function setKeepWithNext($keep = 'auto')
	{
		static $values = array('auto', 'always');
		if (!in_array($keep, $values))
			throw new Dio_PropertyValueInvalid_Exception("Invalid Keep With Next value : '".$keep."'.");

		$this->setAttribute('fo:keep-with-next', $keep);
	}
}