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


class Dio_Office_Meta extends Dio_Element
{
	const		DIO_VERSION = '0.1';
	protected	$metas;

	function __construct()
	{
		parent::__construct('office:meta', null, Dio_Document::NS_OFFICE);
		$this->metas = array();
	}

	function _postAppendChild()
	{
		$this->registerNameSpace('dc', Dio_Document::NS_DC);

		$this->setMeta('generator', 'Dio '.Dio_Office_Meta::DIO_VERSION);
		$this->setMeta('editing-cycles', 1);
		$this->setCreationDate();
	}

	function setCreator($creator)
	{
		$this->setMeta('initial-creator', $creator);
		$this->setDCMeta('creator', $creator);
	}

	function setCreationDate($time = NULL)
	{
		if (is_null($time))
			$time = time();

		$date = strftime("%Y-%m-%dT%H:%M:%S", $time);

		$this->setMeta('creation-date', $date);
		$this->setDCMeta('date', $date);
	}

	function setTitle($title)
	{
		$this->setDCMeta('title', $title);
	}

	function setSubject($subject)
	{
		$this->setDCMeta('subject', $subject);
	}

	function setDescription($desc)
	{
		$this->setDCMeta('description', $desc);
	}

	function setLanguage($lang)
	{
		$this->setDCMeta('language', $desc);
	}

	function setMeta($key, $val)
	{
		// keep metas unique
		if (!isset($this->metas[$key])) {
			$this->metas[$key] = $this->ownerDocument->createElement("meta:".$key, (string)$val);
			$this->appendChild($this->metas[$key]);
		}
	}

	function setDCMeta($key, $val)
	{
		$key = 'dc:'.$key;
		if (!isset($this->metas[$key])) {
			$this->metas[$key] = $this->ownerDocument->createElement($key, $val);
			$this->appendChild($this->metas[$key]);
		}
	}

	function addKeywords($word0)
	{
		$words = func_get_args();
		foreach($words as $word) {
			if (is_array($word)) {
				call_user_func_array(array($this, __FUNCTION__), $word);
			}
			else {
				$meta = $this->ownerDocument->createElement("meta:keyword",$word);
				$this->appendChild($meta);
			}
		}
	}
}
