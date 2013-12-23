<?php

class Strass_Format_ODS extends Strass_Format_ODF
{
	protected $_suffix	= 'ods';
	protected $_mimeType	= Dio_Document::TYPE_SPREADSHEET;
	protected $_title	= 'Tableur';
	protected $_wtkRender	= 'ODS';
}