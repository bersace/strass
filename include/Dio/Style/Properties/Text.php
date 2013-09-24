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

class Dio_Style_Properties_Text extends Dio_Style_Properties
{
	const FONT_STYLE_NORMAL = 'normal';
	const FONT_STYLE_ITALIC	= 'italic';
	const FONT_STYLE_OBLIQUE= 'oblique';

	const WEIGHT_NORMAL	= 'normal';
	const WEIGHT_BOLD	= 'bold';

	function __construct()
	{
		parent::__construct('style:text-properties');
	}


	function setFont($name)
	{
		$this->setAttribute('style:font-name', (string)$name);
	}

	function setWeight($weight)
	{
		static $weights = array(self::WEIGHT_NORMAL, self::WEIGHT_BOLD);

		if (is_numeric($weight))
			$weight = max(1, min(9, intval($weight/100.)))*100;
		else if (is_string($weight) && !in_array($weight, $weights))
			throw new Dio_PropertyValueInvalid_Exception("Weight ".$weight." unsupported.");

		$this->setAttribute('fo:font-weight', $weight);

		return $weight;
	}

	function setSize($size)
	{
		$this->setAttribute('fo:font-size', $size);
	}

	function setFontStyle($style)
	{
		switch($style) {
		case self::FONT_STYLE_NORMAL:
		case self::FONT_STYLE_ITALIC:
		case self::FONT_STYLE_OBLIQUE:
			$this->setAttribute('fo:font-style', $style);
			break;
		default:
			throw new Dio_PropertyValueInvalid_Exception("Invalid font style '".$style."'.");
		}
	}
}