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

class Dio_FontFace extends Dio_Element
{
	protected $name;
	protected $adornments;
	protected $family;
	protected $pitch;
	protected $svgfamily;

	function __construct($name, $adornments = null, $family = 'system', $pitch = 'variable', $svgfamily = null)
	{
		parent::__construct('style:font-face', null, Dio_Document::NS_STYLE);
		$this->name		= $name;
		$this->adornments	= $adornments;
		$this->pitch		= $pitch;
		$this->family		= $family;
		$this->svgfamily	= $svgfamily ? $svgfamily : "'".$name."'";
	}
	function _postAppendChild()
	{
		$this->setAttribute('style:name', $this->name);
		$this->setAttribute('svg:font-family', $this->svgfamily);
		$this->setAttribute('style:font-adornments', $this->adornments);
		$this->setAttribute('style:font-family-generic', $this->family);
		$this->setAttribute('style:font-pitch', $this->pitch);
	}

	function __toString()
	{
		return $this->name;
	}

	function __get($name)
	{
		switch($name) {
		case 'name':
		case 'adornments':
		case 'family':
		case 'pitch':
		case 'svgfamily':
			return $this->$name;
			break;
		}
	}
}