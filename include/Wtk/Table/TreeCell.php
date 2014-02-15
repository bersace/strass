<?php

class Wtk_Table_TreeCell extends Wtk_Container
{
  function __construct($row_path)
  {
    parent::__construct();
    $this->row_path = $row_path;
  }
}