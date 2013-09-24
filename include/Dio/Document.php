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

  /*
   * Extends DomDocument registering Dio node classes, adding
   * namespaces to root element, and calling
   * Dio_Element::_postAppendchild() function on child appending.
   */
class Dio_Document extends DomDocument
{
	const TYPE_TEXT		= 'application/vnd.oasis.opendocument.text';
	const TYPE_SPREADSHEET	= 'application/vnd.oasis.opendocument.spreadsheet';
	const TYPE_CHART	= 'application/vnd.oasis.opendocument.chart';

	const NS_OFFICE		= 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
	const NS_META		= 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0';
	const NS_STYLE		= 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
	const NS_TEXT		= 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
	const NS_TABLE		= 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
	const NS_DRAW		= 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
	const NS_CHART		= 'urn:oasis:names:tc:opendocument:xmlns:chart:1.0';
	const NS_FO		= 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
	const NS_SVG		= 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';

	const NS_DC		= 'http://purl.org/dc/elements/1.1/';
	const NS_XLINK		= 'http://www.w3.org/1999/xlink';


	protected	$_root;
	public		$embeddedNodes = array();

	function __construct($root = null)
	{
		parent::__construct('1.0', 'UTF-8');

		$this->registerNodeClass('DomDocument','Dio_Document');

		$this->registerNodeClass('DomElement', 'Dio_Element');

		$this->registerNodeClass('Dio_Element','Dio_Office_Styles');
		$this->registerNodeClass('Dio_Element','Dio_Office_AutomaticStyles');
		$this->registerNodeClass('Dio_Element','Dio_Office_Meta');
		$this->registerNodeClass('Dio_Element','Dio_Office_Body');
		$this->registerNodeClass('Dio_Element','Dio_Office_Spreadsheet');
		$this->registerNodeClass('Dio_Element','Dio_Office_Text');
		$this->registerNodeClass('Dio_Element','Dio_Office_FontFaceDecls');

		$this->registerNodeClass('Dio_Element','Dio_FontFace');
		$this->registerNodeClass('Dio_Element','Dio_Style');
		$this->registerNodeClass('Dio_Element','Dio_Style_Default');
		$this->registerNodeClass('Dio_Element','Dio_Style_Properties');
		$this->registerNodeClass('Dio_Element','Dio_Style_Properties_Text');
		$this->registerNodeClass('Dio_Element','Dio_Style_Properties_Paragraph');
		$this->registerNodeClass('Dio_Element','Dio_Style_Properties_TableColumn');

		$this->registerNodeClass('Dio_Element','Dio_Table');
		$this->registerNodeClass('Dio_Element','Dio_Table_Column');
		$this->registerNodeClass('Dio_Element','Dio_Table_Columns');
		$this->registerNodeClass('Dio_Element','Dio_Table_Row');
		$this->registerNodeClass('Dio_Element','Dio_Table_Cell');

		$this->registerNodeClass('Dio_Element','Dio_Text_H');	
		$this->registerNodeClass('Dio_Element','Dio_Text_P');
		$this->registerNodeClass('Dio_Element','Dio_Text_Span');
		$this->registerNodeClass('Dio_Element','Dio_Text_A');

		if ($root)
			$this->_setRoot($root);
	}

	protected function _setRoot($root)
	{
		$nss = array('style'	=> self::NS_STYLE,
			     'meta'	=> self::NS_META,
			     'text'	=> self::NS_TEXT,
			     'chart'	=> self::NS_CHART,
			     'svg'	=> self::NS_SVG,
			     'xlink'	=> self::NS_XLINK,
			     'table'	=> self::NS_TABLE,
			     'fo'	=> self::NS_FO);
		$root = $this->_root = $this->appendChild($root);
		$root->setAttribute('office:version', '1.1');

		foreach($nss as $a => $ns)
			$root->registerNameSpace($a, $ns);
	}

	function appendChild($child)
	{
		$child = parent::appendChild($child);
		if ($child instanceof Dio_Element)
			$child->_postAppendChild();
		return $child;
	}

	function embedChild($child)
	{
		// If this document embed directly in XML, just add it
		if ($this instanceof Dio_Embedder)
			return $this->appendChild($child);

		// Else, create a document for it and set the $child
		// as root for this element. The document is still
		// referenced as $child->ownerDocument.
		$doc = new Dio_Document($child);
		array_push($this->embeddedNodes, $child);
		return $child;
	}

	function __get($name)
	{
		if ($name == 'root')
			return $this->_root;
	}

	function __call($method, $args)
	{
		if (preg_match("`^(add|append|embed)(.*)$`", $method, $match)) {
			$class = 'Dio_'.$match[2];
			if (!class_exists($class))
				throw new Dio_UnkownElement_Exception("Element ".$class." is not defined.");
			
			$el = dio_new_user_class_array($class, $args);
			$callback = array($this, str_replace('add', 'append', $match[1]).'Child');
			return call_user_func($callback, $el);
		}
	}
  }