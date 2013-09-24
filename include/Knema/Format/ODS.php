<?php

class Knema_Format_ODS extends Knema_Format_ODF
{
	protected $_suffix	= 'ods';
	protected $_mimeType	= Dio_Document::TYPE_SPREADSHEET;
	protected $_title	= 'Tableur';
	protected $_wtkRender	= 'ODS';
}