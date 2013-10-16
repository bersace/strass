<?php
foreach ($this as $child) {
  wtk_txt_data($child, $_indent, $_width);
  $child->output();
}
