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

class Dio_Style_Properties_TableColumn extends Dio_Element
{
	function __construct()
	{
		parent::__construct('style:table-column-properties', null, Dio_Document::NS_STYLE);
	}

	function useOptimalColumnWidth($use = true)
	{
		$this->setAttribute('style:use-optimal-column-width', $use ? 'true' : 'false');
	}

	function setWidth($width)
	{
		$this->setAttribute('style:column-width', $width);
	}
}