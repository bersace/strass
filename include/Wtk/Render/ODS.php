<?php
require_once 'Wtk/Utils/ODS.php';

class Wtk_Render_ODS extends Wtk_Render_ODF
{
	protected	$template	= 'ods';
	protected	$mime		= Dio_Document::TYPE_SPREADSHEET;
}