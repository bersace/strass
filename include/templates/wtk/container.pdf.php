<?php
foreach ($this->children as $child) {
  $this->$child->output ('pdf');
}
?>