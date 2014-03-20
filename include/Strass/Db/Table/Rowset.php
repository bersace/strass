<?php

class Strass_Db_Table_Rowset extends Zend_Db_Table_Rowset
{
  function __wakeup()
  {
    parent::__wakeup();
    $this->setTable(new $this->_tableClass);
  }
}
