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

require_once 'Dio/Utils.php';

class Dio_UnkownElement_Exception extends Exception {}

  /*
   * A big PITA in PHP/DOM, is that a DomElement is readonly inside
   * __constructor. Dio_Element implement _postAppendChild() function
   * called by Dio_Element::appendChild() and
   * Dio_Document::appendChild(). after the actual adding of the
   * element.
   */
class Dio_Element extends DomElement
{
	public	$nss;
	protected $_attributes = array();

	function __construct($name, $value = null, $ns = '')
	{
		parent::__construct($name, $value, $ns);
		$this->nss = array();
		if ($ns) {
			list($id,$name) = explode(':',$name);
			$this->nss[$id] = $ns;
		}
	}

	// just avoid empty attributes.
	function setAttribute($name, $value)
	{
		if (!$value)
			return;

		$this->_attributes[$name] = $value;
		if ($this->ownerDocument)
			return parent::setAttribute($name, $value);
	}

	function appendChild($child)
	{
		$child = parent::appendChild($child);
		if ($child instanceof Dio_Element)
			$child->_postAppendChild();

		return $child;
	}

	function _postAppendChild()
	{
		foreach($this->_attributes as $name => $value)
			parent::setAttribute($name, $value);
	}

	function embedChild($child)
	{
		// If this document embed directly in XML, just add it
		if ($this->ownerDocument instanceof Dio_Embedder)
			return $this->appendChild($child);

		// Else, create a document for it and set the $child
		// as root for this element. The document is still
		// referenced as $child->ownerDocument.
		$doc = new Dio_Document($child);
		array_push($this->ownerDocument->embeddedNodes, $child);
		return $child;
	}

	function registerNameSpace($id, $uri)
	{
		$root = $this->ownerDocument->root ? $this->ownerDocument->root : $this;
		if (isset($this->nss[$id]) and $root->hasAttribute('xmlns:'.$id))
			return;

		$root->setAttribute('xmlns:'.$id, $uri);
		$this->nss[$id] = $uri;
	}

	function cleanNameSpaces()
	{
		foreach($this->nss as $id => $uri) {
			$this->removeAttribute('xmlns:'.$id);
			unset($this->nss[$id]);
		}
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

	function __get($name)
	{
		if (array_key_exists($name, $this->_attributes))
			return $this->_attributes[$name];
	}
}
