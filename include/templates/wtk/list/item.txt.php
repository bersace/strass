<?php

if ($ordered) {
 }
 else {
	 echo $_indent.' * ';
	 wtk_txt_children_data($this, $_indent.'   ', $_width-2);
	 $item = $this->renderChildren();
	 echo trim($item)."\n";
 }
