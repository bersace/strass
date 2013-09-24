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


class Dio_Document_Manifest extends Dio_Document {
	protected $mimetype;

	function __construct($type)
	{
		parent::__construct();
		$this->root = $this->createElementNS('urn:oasis:names:tc:opendocument:xmlns:manifest:1.0','manifest:manifest');
		$this->appendChild($this->root);
		
		$this->mimetype = $type;
		$this->addFileEntry('/', $type);
	}

	function getMimeType()
	{
		return $this->mimetype;
	}

	function addFileEntry($path, $type, $size = null)
	{
		$entry = $this->createElement('manifest:file-entry');
		$this->root->appendChild($entry);
		$entry->setAttribute('manifest:media-type', $type);
		$entry->setAttribute('manifest:full-path', $path);
		if ($size > 0) {
			$entry->setAttributeNS('manifest:size', $size);
		}
		return $entry;
	}
  }