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


class Dio_Office_Text extends Dio_Element {

	protected $current_level;

	function __construct()
	{
		parent::__construct('office:text', null, Dio_Document::NS_OFFICE);
		$this->current_level=0;
	}

	function openSection($heading='')
	{
		$this->current_level++;
		$h = $this->appendChild(new Dio_Text_H($heading,
						       $this->current_level));
		$h->setAttribute('text:outline-level', $this->current_level);
		return $h;
	}

	function closeSection()
	{
		$this->current_level--;
	}

	function addP($content)
	{
		return $this->appendChild(new Dio_Text_P($content));
	}
  }