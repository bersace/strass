<?php

class Links extends Zend_Db_Table_Abstract
{
  protected	$_name		= 'links';
  protected	$_rowClass	= 'Link';
}

class Link extends Zend_Db_Table_Row_Abstract
{
}
