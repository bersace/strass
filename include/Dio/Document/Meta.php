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


class Dio_Document_Meta extends Dio_Document
{
	protected $_meta;

	function __construct($root = null)
	{
		if (!$root)
			$root = new Dio_Element('office:document-meta', null, Dio_Document::NS_OFFICE);
			
		parent::__construct($root);
		$this->_meta = $this->_root->appendOffice_Meta();
	}

	function __get($name)
	{
		switch($name) {
		case 'meta':
			return $this->_meta;
		default:
			return parent::__get($name);
		}
	}
}
