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

class Dio_Office_FontFaceDecls extends Dio_Element implements Iterator
{
	protected $fonts;

	function __construct()
	{
		parent::__construct('office:font-face-decls', null, Dio_Document::NS_OFFICE);
		$this->fonts = array();
	}

	function _postAppendChild()
	{
		foreach($this->fonts as $font)
			$this->appendChild($font);
	}

	function addFontFace()
	{
		$args = func_get_args();
		$font = parent::__call(__FUNCTION__, $args);
		$this->fonts[$font->name] = $font;
		return $font;
	}

	function __get($name)
	{
		if (isset($this->fonts[$name]))
			return $this->fonts[$name];
		else
			return NULL;
	}


	public function rewind() {
		reset($this->fonts);
	}

	public function current() {
		return current($this->fonts);
	}

	public function key() {
		return  key($this->fonts);
	}

	public function next() {
		return next($this->fonts);
	}

	public function valid() {
		return  $this->current() !== false;
	}
}
