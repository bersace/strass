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

class Dio_Office_Document extends Dio_Element implements Dio_Embeddable
{
	protected	$_type;
	protected	$_embedded_name;

	protected	$_meta;
	protected	$_fonts;
	protected	$_styles;
	protected	$_astyles;
	protected	$_body;

	// For embedded document, just wrap those documents.
	protected	$_metad;
	protected	$_stylesd;
	protected	$_contentd;


	function __construct($type, $embedded = null)
	{
		static $count = 0;
		parent::__construct('office:document', null, Dio_Document::NS_OFFICE);

		$this->_type		= $type;
		$this->_embedded_name	= $embedded ? "Object ".++$count : null;
	}

	function _postAppendChild()
	{
		$this->setAttribute('office:mimetype', $this->_type);
		if ($this->ownerDocument instanceof Dio_Embedder) {
			$this->_meta	= $this->addOffice_Meta();
			if (!$this->_embedded_name)
				$this->_fonts	= $this->addOffice_FontFaceDecls();
			$this->_styles	= $this->addOffice_Styles();
			$this->_astyles	= $this->addOffice_AutomaticStyles();
			$this->_body	= $this->addOffice_Body($this->_type);
		}
		else {
			// We are embedded in a .zip, simulate being
			// another Archive inside the archive
			$this->_contentd= new Dio_Document_Content($this->_type);
			$this->_metad	= new Dio_Document_Meta;
			$this->_stylesd	= new Dio_Document_Styles;

			$this->_body	= $this->_contentd->body;
			$this->_meta	= $this->_metad->meta;
			$this->_styles	= $this->_stylesd->styles;
			// beware that bodyd also have a
			// astyles. Needs to check the right one to
			// expose.
			$this->_astyles = $this->_stylesd->astyles;
		}
	}
 
	function __get($name)
	{
		switch($name) {
		case 'content':
			// retrieve directly format specific content (spreadsheet, text, etc.)
			return $this->_body->document;
		case 'meta':
		case 'metas':
			$name = 'meta';
		case 'body':
		case 'styles':
		case 'fonts':
		case 'astyles':
			$prop = '_'.$name;
			return $this->$prop;
			break;
		case 'automaticStyles':
			return $this->_styles->astyles;
		case 'mimetype':
			return $this->_type;
		default:
			return parent::__get($name);
		}
	}

	function __set($name, $value)
	{
		switch($name) {
		case 'embedded_name':
			$this->_embedded_name = $name;
			break;
		default:
			parent::__set($name, $value);
			break;
		}
	}

	function getFullPath()
	{
		return $this->_embedded_name.'/';
	}

	function getMimeType()
	{
		return $this->_type;
	}

	function getHref()
	{
		return './'.$this->_embedded_name;
	}

	function getFileList()
	{
		return array('content.xml' => 'text/xml',
			     'meta.xml' => 'text/xml',
			     'styles.xml' => 'text/xml');
	}

	function getFileContent($filename)
	{
		switch($filename) {
		case 'content.xml':
		case 'meta.xml':
		case 'styles.xml':
			$var = '_'.str_replace('.xml', '', $filename).'d';
			return $this->$var->saveXML();
			break;
		default:
			throw new Exception("Unable to generate ".$filename." content for ".get_class($this)." ".$this->_embedded_name.".");
			break;
		}
	}
}
