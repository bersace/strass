<?php
if($show_header) {
	foreach($columns as $i => $column) {
		$_context->table->columns->addColumn($column->getTitle());
	}
 }


for($i = 0; $i < $rows; $i++) {
	for($j = 0; $j < $cols; $j++) {
		$_context->y = $j;
		$id = 'cell-'.$i.'-'.$j;
		wtk_context($this->$id, $_context);
		$this->$id->output();
		//$_context->table->put($_context->x, $_context->y, $id);
	}
	$_context->x++;
 }
