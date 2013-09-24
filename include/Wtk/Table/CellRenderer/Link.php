<?php

class Wtk_Table_CellRenderer_Link extends Wtk_Table_CellRenderer
{
  protected $properties = array ('href' => '',
				 'label' => '');
  protected	$urlFormat = '%s';

  function setUrlFormat($format)
  {
    $this->urlFormat = $format;
  }

  function element($data)
  {
    return new Wtk_Link (sprintf($this->urlFormat, $data['href']), $data['label']);
  }
}