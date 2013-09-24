<?php
wtk_children_context($this, $_context);
// level 1 = document
// level 2 = section
// GRUIK
if ($level > 1) {
	if ($this->title) {
		$_context->y = array(0,4);
		wtk_context($this->title, $_context);
		$this->title->output();
		$cell = $_context->table->get($_context->x, 0);
		$h2 = $_context->doc->styles->getStyle('Heading_2');
		$cell->setStyle($h2);
		$_context->x++;
	}
 }
 else if ($level == 1 and isset($this->title)) {
	 $_context->table = $_context->content->addTable($stringTitle);
	 $_context->x = 0;
 }

$_context->y = 0;

$this->outputChildren();