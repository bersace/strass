<?php

  /***************************************************************************

PHP vCard class v2.0
(c) Kai Blankenhorn
www.bitfolge.de/en
kaib@bitfolge.de


This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

  ***************************************************************************/


function escape($string)
{
	return trim(str_replace(";","\;",$string));
}

class vCard {
	protected $properties;
	protected $filename;
    
	/*
	 * type may be PREF | WORK | HOME | VOICE | FAX | MSG | CELL | PAGER | BBS | CAR | MODEM | ISDN | VIDEO
	 * or any senseful combination, e.g. "PREF;WORK;VOICE"
	 */
	function setPhoneNumber($number, $type="")
	{
		$key = "TEL";
		if ($type!="") $key .= ";".$type;
		$key.= ";ENCODING=UTF-8";
		$this->properties[$key] = $number;
	}
    
	// $type = "GIF" | "JPEG"
	function setPhoto($type, $photo)
	{
		$this->properties["PHOTO;TYPE=$type;ENCODING=BASE64"] = base64_encode($photo);
	}
    
	function setFormattedName($name)
	{
		$this->properties["FN"] = $name;
	}
    
	function setName($family="", $first="", $additional="", $prefix="", $suffix="")
	{
		$this->properties["N"] = "$family;$first;$additional;$prefix;$suffix";
		$this->filename = str_replace(' ', '_', "$first $family.vcf");
		if (!isset($this->properties["FN"]))
			$this->setFormattedName(trim("$prefix $first $additional $family $suffix"));
	}
    
	// $date format is YYYY-MM-DD
	function setBirthday($date)
	{
		$this->properties["BDAY"] = $date;
	}
    
	// $type may be DOM | INTL | POSTAL | PARCEL | HOME | WORK or any combination of these: e.g. "WORK;PARCEL;POSTAL"
	function setAddress($postoffice="", $extended="", $street="", $city="",
			    $region="", $zip="", $country="", $type="HOME;POSTAL")
	{
		$key = "ADR";
		if ($type!="")
			$key.= ";$type";
		$key.= ";ENCODING=UTF-8";

		$this->properties[$key] =
			escape($postoffice).";".escape($extended).";".
			escape($street).";".escape($city).";".
			escape($region).";".escape($zip).";".
			escape($country);
	}
    
	function setLabel($postoffice="", $extended="", $street="", $city="", $region="", $zip="",
			  $country="", $type="HOME;POSTAL")
	{
		$label = "";
		if ($postoffice!="") $label.= "$postoffice\r\n";
		if ($extended!="") $label.= "$extended\r\n";
		if ($street!="") $label.= "$street\r\n";
		if ($zip!="") $label.= "$zip ";
		if ($city!="") $label.= "$city\r\n";
		if ($region!="") $label.= "$region\r\n";
		if ($country!="") $country.= "$country\r\n";
        
		$this->properties["LABEL;$type;ENCODING=UTF-8"] = $label;
	}
    
	function setEmail($address)
	{
		$this->properties["EMAIL;INTERNET"] = $address;
	}

	function setJabber($jabberid)
	{
		$this->properties["X-JABBER"] = $jabberid;
	}

	function setNote($note)
	{
		$this->properties["NOTE;ENCODING=UTF-8"] = $note;
	}
    
	function setURL($url, $type="")
	{
		// $type may be WORK | HOME
		$key = "URL";
		if ($type!="") $key.= ";$type";
		$this->properties[$key] = $url;
	}
    
	function getVCard()
	{
		$text = "BEGIN:VCARD\r\n";
		$text.= "VERSION:2.1\r\n";
		foreach($this->properties as $key => $value) {
			$text.= "$key:$value\r\n";
		}
		$text.= "REV:".date("Y-m-d")."T".date("H:i:s")."Z\r\n";
		$text.= "END:VCARD\r\n";
		return $text;
	}
    
	function getFileName()
	{
		return $this->filename;
	}
}

return;

//  USAGE EXAMPLE

$v = new vCard();

$v->setPhoneNumber("+49 23 456789", "PREF;HOME;VOICE");
$v->setName("Mustermann", "Thomas", "", "Herr");
$v->setBirthday("1960-07-31");
$v->setAddress("", "", "Musterstrasse 20", "Musterstadt", "", "98765", "Deutschland");
$v->setEmail("thomas.mustermann@thomas-mustermann.de");
$v->setNote("You can take some notes here.\r\nMultiple lines are supported via \\r\\n.");
$v->setURL("http://www.thomas-mustermann.de", "WORK");

$output = $v->getVCard();
$filename = $v->getFileName();

Header("Content-Disposition: attachment; filename=$filename");
Header("Content-Length: ".strlen($output));
Header("Connection: close");
Header("Content-Type: text/x-vCard; name=$filename");

echo $output;
