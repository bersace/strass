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

class Dio_Style extends Dio_Element
{
	protected $name;
	protected $display_name;
	protected $family;
	protected $parent;
	protected $next;
	protected $propertySets;
	protected $classes;
	protected $tag = 'style:style';

	// See ODF 1.0 page 461.
	const FAMILY_PARAGRAPH		= 'paragraph';
	const FAMILY_TEXT		= 'text';
	const FAMILY_SECTION		= 'section';
	const FAMILY_TABLE		= 'table';
	const FAMILY_TABLE_COLUMN	= 'table-column';
	const FAMILY_TABLE_ROW		= 'table-row';
	const FAMILY_TABLE_CELL		= 'table-cell';
	const FAMILY_TABLE_PAGE		= 'table-page';
	const FAMILY_CHART		= 'chart';
	const FAMILY_DEFAULT		= 'default';
	const FAMILY_DRAWING_PAGE	= 'drawing-page';
	const FAMILY_GRAPHIC		= 'graphic';
	const FAMILY_PRESENTATION	= 'presentation';
	const FAMILY_CONTROL		= 'control';
	const FAMILY_RUBY		= 'ruby';

	function __construct($display_name, $family, $parent = NULL, $next = NULL)
	{
		parent::__construct($this->tag, null, Dio_Document::NS_STYLE);
		$this->family	= $family;
		$this->name	= dio_strtoid($display_name);
		$this->display_name=$display_name;
		$this->parent	= $parent;
		$this->next	= $next;
		$this->propertySets = array();
		$this->classes	= array($family);
	}

	function addProperties($set)
	{
		if ($this->ownerDocument)
			$set = $this->appendChild($set);

		$this->propertySets[$set->nodeName] = $set;
		return $set;
	}

	function _postAppendChild()
	{
		$this->setAttribute('style:name', $this->name);
		$this->setAttribute('style:display-name', $this->display_name);
		$this->setAttribute('style:parent-style-name', (string)$this->parent);
		$this->setAttribute('style:next-style-name', (string)$this->next);
		$this->setAttribute('style:family', $this->family);
		$this->setAttribute('style:class', implode(' ', $this->classes));

		foreach($this->propertySets as $key => $set)
			$this->propertySets[$key] = $this->appendChild($set);
	}

	function __call($method, $args)
	{
		if (preg_match("`^addProperties([^_].*)$`", $method, $match))
			$method = 'addStyle_Properties_'.$match[1];

		$el = parent::__call($method, $args);

		if ($el instanceof Dio_Properties)
			$this->propertySets[$el->nodeName] = $el;

		return $el;
	}

	function __get($name)
	{
		switch($name) {
		case 'propertySets':
		case 'name':
		case 'display_name':
			return $this->$name;
		}
	}

	function __toString()
	{
		return $this->name;
	}
}