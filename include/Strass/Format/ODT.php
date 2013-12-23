<?php

class Strass_Format_ODT extends Strass_Format_ODF
{
	protected $_suffix	= 'odt';
	protected $_mimeType	= Dio_Document::TYPE_TEXT;
	protected $_title	= 'Texteur';
	protected $_wtkRender	= 'ODT';
}