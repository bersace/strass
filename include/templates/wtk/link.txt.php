<?php
wtk_txt_children_data($this, $_indent, $_width);
$this->outputChildren();
echo " <".(strpos($href, 'http://') === FALSE ? 'http://'.$_SERVER['HTTP_HOST'].$href : $href).">";
