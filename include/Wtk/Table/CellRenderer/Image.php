<?php

class Wtk_Table_CellRenderer_Image extends Wtk_Table_CellRenderer
{
  protected $properties = array ('url' => null,
				 'alt' => null,
				 'title' => null,
				 'width' => null,
				 'height' => null);
  function element($data)
  {
    return new Wtk_Image ($data['url'],
			  $data['alt'],
			  new Wtk_Metas(array ('title' => $data['title'])),
			  $data['width'],
			  $data['height']);
  }
}