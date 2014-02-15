<?php

class Wtk_Table_CellRenderer_Link extends Wtk_Table_CellRenderer
{
  protected $properties = array ('href' => '',
				 'label' => '',
				 'flags' => '');
  protected	$urlFormat = '%s';

  function setUrlFormat($format)
  {
    $this->urlFormat = $format;
  }

  function element($data)
  {
    $href = $data['href'];
    if ($href) {
      $link = new Wtk_Link(sprintf($this->urlFormat, $href), $data['label']);
      return $link->addFlags($data['flags']);
    }
    else
      return new Wtk_RawText($data['label']);
  }
}