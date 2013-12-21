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

require_once 'ZipArchive.php';

  /*
   * Implement ODT archive format.
   */
class Dio_Archive extends ZipArchive
{
	protected	$_mimetype;
	protected	$_manifest;
	protected	$_content;
	protected	$_styles;
	protected	$_meta;

	protected	$_filename;

	/*
	 * $type	an ODF mimetype from Dio_Document.
	 */
	function __construct($type)
	{
		$this->_mimetype= $type;
		$this->_manifest= new Dio_Document_Manifest($type);
		$this->_content	= new Dio_Document_Content($type);
		$this->_meta	= new Dio_Document_Meta;
		$this->_styles	= new Dio_Document_Styles;

		$this->_filename = tempnam(null, 'dio-');

		$this->open($this->_filename, ZipArchive::OVERWRITE);
		$this->addEmptyDir('META-INF');
	}

	protected function _addFile($path, $type, $content)
	{
		$this->addFromString($path, $content);
		$this->_manifest->addFileEntry($path, $type);
	}

	function render()
	{
		$this->addFromString('mimetype', $this->_mimetype);
		$this->_content->copyFonts($this->_styles->fonts);
		$this->_addFile('content.xml', 'text/xml', $this->_content->saveXML());
		$this->_addFile('styles.xml', 'text/xml', $this->_styles->saveXML());
		$this->_addFile('meta.xml', 'text/xml', $this->_meta->saveXML());

		foreach($this->_content->embeddedNodes as $object)
			$this->_renderEmbedded($object);

		$this->addFromString('META-INF/manifest.xml', $this->_manifest->saveXML());
		$this->close();
		$output = file_get_contents($this->_filename);
		unlink($this->_filename);
		return $output;
	}

	function _renderEmbedded(Dio_Embeddable $object)
	{
		$dirname = $object->getFullPath();

		$files = $object->getFileList();
		foreach($files as $filename => $type) {
			$this->_addFile($dirname.$filename, $type, $object->getFileContent($filename));
		}
		$this->_manifest->addFileEntry($object->getFullPath(), $object->getMimeType());
	}

	function __get($name)
	{
		switch($name) {
		case 'content':
			// retrieve directly format specific content (spreadsheet, text, etc.)
			return $this->_content->document;
		case 'meta':
		case 'metas':
			return $this->_meta->meta;
			break;
		case 'fonts':
		case 'styles':
		case 'astyles':
			return $this->_styles->$name;
			break;
		case 'mimetype':
			//return 'application/zip';
			return $this->_manifest->getMimeType();
		default:
			return NULL;
		}
	}
  }