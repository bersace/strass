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

  /*
   * Implement Flat XML ODT file.
   */
class Dio_Flat extends Dio_Document implements Dio_Embedder
{
	/*
	 *
	 * $type	an ODF mimetype from Dio_Document.
	 */
	function __construct($type)
	{
		parent::__construct();
		$this->_setRoot(new Dio_Office_Document($type));
	}


	function importRoot(DomDocument $doc)
	{
		$root = clone $doc->root;
		$root->cleanNameSpaces();
		$root = $this->importNode($doc->root, true);
		$root = $this->root->appendChild($root);
	}

	function render()
	{
		return $this->saveXML();
	}

	function __get($name)
	{
		switch($name) {
		case 'content':
		case 'metas':
		case 'styles':
		case 'fonts':
		case 'astyles':
		case 'automaticStyles':
		case 'mimetype':
			return $this->root->$name;
		default:
			return parent::__get($name);
		}
	}
  }